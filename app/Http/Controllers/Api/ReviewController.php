<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\GameReview;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    const VALID_TAGS = [
        'Proses Cepat',
        'Terpercaya',
        'Harga Terjangkau',
        'Direkomendasikan',
        'Pelayanan Ramah',
    ];

    /**
     * Simpan review. Auth via invoice_id (bukan login) — berlaku untuk guest dan user.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'invoice_id' => 'required|string',
            'rating' => 'required|integer|min:1|max:5',
            'tags' => 'nullable|array|max:5',
            'tags.*' => 'string|in:'.implode(',', self::VALID_TAGS),
        ]);

        // Autentikasi via invoice_id — hanya transaksi success yang boleh direview
        $transaction = Transaction::with('product')
            ->where('invoice_id', $validated['invoice_id'])
            ->where('status', 'success')
            ->first();

        if (! $transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi tidak ditemukan atau belum selesai.',
            ], 404);
        }

        // Cek sudah pernah review
        if (GameReview::where('transaction_id', $validated['invoice_id'])->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Kamu sudah memberikan ulasan untuk transaksi ini.',
            ], 409);
        }

        $gameId = $transaction->product->game_id;

        DB::transaction(function () use ($validated, $transaction, $gameId) {
            GameReview::create([
                'game_id' => $gameId,
                'transaction_id' => $validated['invoice_id'],
                'user_id' => $transaction->user_id,
                'rating' => $validated['rating'],
                'tags' => $validated['tags'] ?? [],
            ]);

            // Update cached rating & reviews_count di tabel games
            $avg = GameReview::where('game_id', $gameId)->avg('rating');
            $count = GameReview::where('game_id', $gameId)->count();

            Game::where('id', $gameId)->update([
                'rating' => round((float) $avg, 2),
                'reviews_count' => $count,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Terima kasih atas ulasanmu!',
        ]);
    }

    /**
     * Cek apakah invoice sudah direview.
     */
    public function check(Request $request)
    {
        $validated = $request->validate([
            'invoice_id' => 'required|string',
        ]);

        $exists = GameReview::where('transaction_id', $validated['invoice_id'])->exists();

        return response()->json([
            'success' => true,
            'has_reviewed' => $exists,
        ]);
    }
}
