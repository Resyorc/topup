<?php

namespace App\Jobs;

use App\Models\Transaction;
use App\Models\CoinTopup;
use App\Services\FonnteService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendWhatsAppNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $backoff = 10;

    public function __construct(
        public readonly string $phone,
        public readonly string $message,
    ) {}

    public function handle(FonnteService $fonnte): void
    {
        $fonnte->send($this->phone, $this->message);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Factory methods — satu tempat untuk semua template pesan
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Notifikasi order baru (status: pending) — pembayaran belum dilakukan.
     */
    public static function orderPending(Transaction $transaction): static
    {
        $product  = $transaction->product;
        $gameName = $product->game->name ?? 'Game';
        $name     = $transaction->customer_name ?? 'Pelanggan';
        $expiredAt = $transaction->expired_at
            ? $transaction->expired_at->timezone('Asia/Jakarta')->format('d M Y, H:i') . ' WIB'
            : '-';

        $total = number_format((int) $transaction->amount + (int) $transaction->fee, 0, ',', '.');

        $message = "Halo *{$name}*! 👋\n\n"
            . "Pesanan kamu sudah kami terima.\n\n"
            . "🎮 *{$gameName}* — {$product->name}\n"
            . "🆔 ID Akun: {$transaction->customer_game_id}\n"
            . "📋 Invoice: `{$transaction->invoice_id}`\n"
            . "💰 Total: Rp {$total}\n"
            . "⏰ Batas bayar: {$expiredAt}\n\n"
            . "Silakan selesaikan pembayaran sebelum waktu habis.\n"
            . "Terima kasih sudah berbelanja di *Nuvelo*! 🙏";

        return new static($transaction->customer_whatsapp, $message);
    }

    /**
     * Notifikasi pembayaran diterima (status: processing).
     */
    public static function paymentReceived(Transaction $transaction): static
    {
        $product  = $transaction->product;
        $gameName = $product->game->name ?? 'Game';
        $name     = $transaction->customer_name ?? 'Pelanggan';

        $message = "✅ *Pembayaran Diterima!*\n\n"
            . "Halo *{$name}*, pembayaran kamu untuk:\n\n"
            . "🎮 *{$gameName}* — {$product->name}\n"
            . "🆔 ID Akun: {$transaction->customer_game_id}\n"
            . "📋 Invoice: `{$transaction->invoice_id}`\n\n"
            . "Topup sedang diproses, mohon tunggu sebentar ⏳";

        return new static($transaction->customer_whatsapp, $message);
    }

    /**
     * Notifikasi topup berhasil (status: success).
     * Jika ada SN (voucher code), ikutkan dalam pesan.
     */
    public static function topupSuccess(Transaction $transaction): static
    {
        $product  = $transaction->product;
        $gameName = $product->game->name ?? 'Game';
        $name     = $transaction->customer_name ?? 'Pelanggan';

        $snLine = $transaction->sn
            ? "\n🎟️ Kode Voucher: `{$transaction->sn}`"
            : '';

        $loyaltyCoins = (int) $transaction->loyalty_coins;
        $loyaltyLine  = $loyaltyCoins > 0
            ? "\n\n🪙 *+{$loyaltyCoins} Krysta Coin* berhasil ditambahkan sebagai reward loyalitas!"
            : '';

        $message = "🎉 *Topup Berhasil!*\n\n"
            . "Halo *{$name}*!\n\n"
            . "🎮 *{$gameName}* — {$product->name}\n"
            . "🆔 ID Akun: {$transaction->customer_game_id}\n"
            . "📋 Invoice: `{$transaction->invoice_id}`"
            . $snLine
            . $loyaltyLine . "\n\n"
            . "Sudah masuk ke akun kamu. Selamat bermain! 🕹️\n"
            . "Terima kasih sudah berbelanja di *Nuvelo*! 🙏";

        return new static($transaction->customer_whatsapp, $message);
    }

    /**
     * Notifikasi topup gagal (status: failed).
     */
    public static function topupFailed(Transaction $transaction): static
    {
        $product  = $transaction->product;
        $gameName = $product->game->name ?? 'Game';
        $name     = $transaction->customer_name ?? 'Pelanggan';

        $refundLine = $transaction->payment_method === 'COIN'
            ? "\n💰 Saldo Krysta Coin kamu sudah dikembalikan secara otomatis."
            : '';

        $message = "❌ *Topup Gagal*\n\n"
            . "Maaf *{$name}*, topup untuk:\n\n"
            . "🎮 *{$gameName}* — {$product->name}\n"
            . "🆔 ID Akun: {$transaction->customer_game_id}\n"
            . "📋 Invoice: `{$transaction->invoice_id}`\n"
            . "gagal diproses."
            . $refundLine . "\n\n"
            . "Hubungi CS kami jika butuh bantuan. 🙏";

        return new static($transaction->customer_whatsapp, $message);
    }

    /**
     * Notifikasi top up Krysta Coin berhasil.
     */
    public static function coinTopupSuccess(CoinTopup $coinTopup): static
    {
        $userName = $coinTopup->user?->name ?? 'Pelanggan';
        $amount   = number_format((int) $coinTopup->amount, 0, ',', '.');

        $message = "🪙 *Krysta Coin Berhasil Ditambahkan!*\n\n"
            . "Halo *{$userName}*!\n\n"
            . "💰 *Rp {$amount}* Krysta Coin sudah masuk ke akun kamu.\n"
            . "📋 Invoice: `{$coinTopup->invoice_id}`\n\n"
            . "Terima kasih sudah top up Krysta Coin di *Nuvelo*! 🙏";

        return new static($coinTopup->customer_whatsapp, $message);
    }
}
