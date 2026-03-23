<?php

namespace App\Services;

use App\Models\BroadcastMessage;
use App\Models\CoinTransaction;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BusinessAgentTools
{
    private const WRITE_TOOLS = [
        'create_promo_code',
        'deactivate_promo',
        'update_product_price',
        'send_notification',
        'adjust_loyalty_points',
    ];

    public static function definitions(): array
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_products',
                    'description' => 'Ambil daftar produk beserta harga jual, HPP, dan margin. Gunakan untuk analisis profitabilitas produk.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'active_only' => ['type' => 'boolean', 'description' => 'Hanya tampilkan produk yang aktif (default: true)'],
                            'game_name' => ['type' => 'string', 'description' => 'Filter berdasarkan nama game (opsional)'],
                            'limit' => ['type' => 'integer', 'description' => 'Jumlah data (default: 50, maks: 200)'],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_transactions',
                    'description' => 'Ambil data transaksi N hari terakhir. Gunakan untuk analisis penjualan dan revenue.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'days' => ['type' => 'integer', 'description' => 'Rentang hari ke belakang (default: 30)'],
                            'status' => ['type' => 'string', 'enum' => ['success', 'failed', 'pending', 'all'], 'description' => 'Filter status transaksi (default: all)'],
                            'limit' => ['type' => 'integer', 'description' => 'Jumlah data (default: 100, maks: 500)'],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_customers',
                    'description' => 'Ambil data pelanggan. Gunakan untuk segmentasi dan analisis retensi.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'segment' => [
                                'type' => 'string',
                                'enum' => ['all', 'active', 'inactive', 'top_spenders', 'new'],
                                'description' => 'Segmen pelanggan: active=beli <30hr, inactive=tidak beli ≥45hr, top_spenders=revenue terbesar, new=<7hr',
                            ],
                            'limit' => ['type' => 'integer', 'description' => 'Jumlah data (default: 50)'],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_promo_codes',
                    'description' => 'Ambil semua kode promo/voucher dan statistik penggunaannya.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'active_only' => ['type' => 'boolean', 'description' => 'Hanya yang aktif (default: false)'],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_sales_report',
                    'description' => 'Ambil laporan penjualan agregat: revenue, profit, jumlah transaksi, dibandingkan periode sebelumnya.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'period' => [
                                'type' => 'string',
                                'enum' => ['today', 'week', 'month', 'last_month'],
                                'description' => 'Periode laporan',
                            ],
                        ],
                        'required' => ['period'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'create_promo_code',
                    'description' => 'Buat kode promo/voucher baru. WAJIB minta konfirmasi sebelum memanggil tool ini.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'code' => ['type' => 'string', 'description' => 'Kode voucher (uppercase, tanpa spasi)'],
                            'type' => ['type' => 'string', 'enum' => ['percent', 'flat'], 'description' => 'Jenis diskon: percent atau nominal flat'],
                            'value' => ['type' => 'number', 'description' => 'Nilai diskon (persen 1-100 atau nominal Rupiah)'],
                            'min_amount' => ['type' => 'number', 'description' => 'Minimum pembelian (Rupiah)'],
                            'max_discount' => ['type' => 'number', 'description' => 'Maksimum nominal diskon (Rupiah, opsional untuk tipe percent)'],
                            'usage_limit' => ['type' => 'integer', 'description' => 'Batas total penggunaan'],
                            'valid_from' => ['type' => 'string', 'description' => 'Tanggal mulai (format: YYYY-MM-DD)'],
                            'valid_until' => ['type' => 'string', 'description' => 'Tanggal berakhir (format: YYYY-MM-DD)'],
                            'is_public' => ['type' => 'boolean', 'description' => 'Tampil publik di halaman promo (default: false)'],
                        ],
                        'required' => ['code', 'type', 'value', 'min_amount', 'usage_limit', 'valid_until'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'deactivate_promo',
                    'description' => 'Nonaktifkan kode promo. WAJIB minta konfirmasi sebelum memanggil tool ini.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'code' => ['type' => 'string', 'description' => 'Kode voucher yang akan dinonaktifkan'],
                        ],
                        'required' => ['code'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'update_product_price',
                    'description' => 'Update harga jual produk. WAJIB minta konfirmasi sebelum memanggil tool ini.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'product_id' => ['type' => 'integer', 'description' => 'ID produk yang akan diupdate'],
                            'new_price_sell' => ['type' => 'number', 'description' => 'Harga jual baru (Rupiah)'],
                        ],
                        'required' => ['product_id', 'new_price_sell'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'send_notification',
                    'description' => 'Kirim broadcast notifikasi ke semua pengguna. WAJIB minta konfirmasi sebelum memanggil tool ini.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'message' => ['type' => 'string', 'description' => 'Isi pesan notifikasi (maks 500 karakter)'],
                        ],
                        'required' => ['message'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'adjust_loyalty_points',
                    'description' => 'Koreksi saldo koin loyalitas pelanggan. WAJIB minta konfirmasi sebelum memanggil tool ini.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'user_id' => ['type' => 'integer', 'description' => 'ID pengguna'],
                            'delta' => ['type' => 'integer', 'description' => 'Jumlah koin (positif=tambah, negatif=kurangi)'],
                            'reason' => ['type' => 'string', 'description' => 'Alasan penyesuaian (wajib untuk audit trail)'],
                        ],
                        'required' => ['user_id', 'delta', 'reason'],
                    ],
                ],
            ],
        ];
    }

    public static function requiresConfirmation(string $tool): bool
    {
        return in_array($tool, self::WRITE_TOOLS, true);
    }

    public static function execute(string $tool, array $args): string
    {
        try {
            return match ($tool) {
                'get_products'         => self::getProducts($args),
                'get_transactions'     => self::getTransactions($args),
                'get_customers'        => self::getCustomers($args),
                'get_promo_codes'      => self::getPromoCodes($args),
                'get_sales_report'     => self::getSalesReport($args),
                'create_promo_code'    => self::createPromoCode($args),
                'deactivate_promo'     => self::deactivatePromo($args),
                'update_product_price' => self::updateProductPrice($args),
                'send_notification'    => self::sendNotification($args),
                'adjust_loyalty_points' => self::adjustLoyaltyPoints($args),
                default => json_encode(['error' => "Tool tidak dikenal: $tool"]),
            };
        } catch (\Throwable $e) {
            return json_encode(['error' => $e->getMessage()]);
        }
    }

    public static function describeAction(string $tool, array $args): string
    {
        return match ($tool) {
            'create_promo_code' => sprintf(
                'Buat kode promo **%s** (diskon %s%s, min. beli Rp %s, berlaku s/d %s, maks %d penggunaan)',
                strtoupper($args['code'] ?? ''),
                $args['value'] ?? '?',
                $args['type'] === 'percent' ? '%' : ' flat',
                number_format($args['min_amount'] ?? 0, 0, ',', '.'),
                $args['valid_until'] ?? '?',
                $args['usage_limit'] ?? 0,
            ),
            'deactivate_promo' => sprintf('Nonaktifkan kode promo **%s**', $args['code'] ?? ''),
            'update_product_price' => sprintf(
                'Update harga produk ID %d menjadi **Rp %s**',
                $args['product_id'] ?? 0,
                number_format($args['new_price_sell'] ?? 0, 0, ',', '.'),
            ),
            'send_notification' => sprintf('Kirim broadcast: *"%s"*', mb_substr($args['message'] ?? '', 0, 100)),
            'adjust_loyalty_points' => sprintf(
                '%s **%d koin** untuk user ID %d (%s)',
                ($args['delta'] ?? 0) > 0 ? 'Tambah' : 'Kurangi',
                abs($args['delta'] ?? 0),
                $args['user_id'] ?? 0,
                $args['reason'] ?? '-',
            ),
            default => "Jalankan: $tool",
        };
    }

    // ── READ TOOLS ──────────────────────────────────────────────────────────

    private static function getProducts(array $args): string
    {
        $activeOnly = $args['active_only'] ?? true;
        $limit = min((int) ($args['limit'] ?? 50), 200);

        $query = Product::with('game:id,name')
            ->when($activeOnly, fn ($q) => $q->where('is_available', true))
            ->when(! empty($args['game_name']), function ($q) use ($args) {
                $q->whereHas('game', fn ($g) => $g->where('name', 'like', '%'.$args['game_name'].'%'));
            })
            ->limit($limit)
            ->get(['id', 'game_id', 'name', 'group', 'price_cost', 'price_sell', 'margin_percent', 'margin_flat', 'is_available']);

        $data = $query->map(fn ($p) => [
            'id'           => $p->id,
            'game'         => $p->game?->name,
            'name'         => $p->name,
            'group'        => $p->group,
            'price_cost'   => $p->price_cost,
            'price_sell'   => $p->price_sell,
            'margin_pct'   => $p->margin_percent,
            'margin_flat'  => $p->margin_flat,
            'is_available' => $p->is_available,
        ]);

        return json_encode(['count' => $data->count(), 'products' => $data]);
    }

    private static function getTransactions(array $args): string
    {
        $days = (int) ($args['days'] ?? 30);
        $status = $args['status'] ?? 'all';
        $limit = min((int) ($args['limit'] ?? 100), 500);

        $transactions = Transaction::with('product:id,name')
            ->where('created_at', '>=', Carbon::now()->subDays($days))
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->latest()
            ->limit($limit)
            ->get(['id', 'invoice_id', 'product_id', 'amount', 'fee', 'profit', 'voucher_code', 'discount', 'status', 'payment_method', 'created_at']);

        $data = $transactions->map(fn ($t) => [
            'invoice'        => $t->invoice_id,
            'product'        => $t->product?->name,
            'amount'         => $t->amount,
            'profit'         => $t->profit,
            'voucher'        => $t->voucher_code,
            'discount'       => $t->discount,
            'status'         => $t->status,
            'payment_method' => $t->payment_method,
            'date'           => $t->created_at->format('Y-m-d H:i'),
        ]);

        $summary = [
            'total_revenue' => $transactions->sum('amount'),
            'total_profit'  => $transactions->sum('profit'),
            'count'         => $transactions->count(),
        ];

        return json_encode(['summary' => $summary, 'transactions' => $data]);
    }

    private static function getCustomers(array $args): string
    {
        $segment = $args['segment'] ?? 'all';
        $limit = min((int) ($args['limit'] ?? 50), 200);

        $query = User::query();

        switch ($segment) {
            case 'active':
                $query->whereHas('transactions', fn ($q) => $q->where('status', 'success')->where('created_at', '>=', Carbon::now()->subDays(30)));
                break;
            case 'inactive':
                $query->whereHas('transactions', fn ($q) => $q->where('status', 'success'))
                    ->whereDoesntHave('transactions', fn ($q) => $q->where('status', 'success')->where('created_at', '>=', Carbon::now()->subDays(45)));
                break;
            case 'new':
                $query->where('created_at', '>=', Carbon::now()->subDays(7));
                break;
            case 'top_spenders':
                $query->withSum(['transactions as total_spent' => fn ($q) => $q->where('status', 'success')], 'amount')
                    ->orderByDesc('total_spent');
                break;
        }

        $users = $query->limit($limit)->get(['id', 'name', 'email', 'created_at']);

        $result = $users->map(function ($u) {
            $lastTx = Transaction::where('user_id', $u->id)->where('status', 'success')->latest()->first(['created_at', 'amount']);

            return [
                'id'           => $u->id,
                'name'         => $u->name,
                'email'        => $u->email,
                'joined'       => $u->created_at->format('Y-m-d'),
                'last_order'   => $lastTx?->created_at->format('Y-m-d'),
                'last_amount'  => $lastTx?->amount,
            ];
        });

        return json_encode(['segment' => $segment, 'count' => $result->count(), 'customers' => $result]);
    }

    private static function getPromoCodes(array $args): string
    {
        $activeOnly = $args['active_only'] ?? false;

        $vouchers = Voucher::when($activeOnly, fn ($q) => $q->where('is_active', true))
            ->orderByDesc('created_at')
            ->get();

        $data = $vouchers->map(fn ($v) => [
            'code'        => $v->code,
            'type'        => $v->type,
            'value'       => $v->value,
            'min_amount'  => $v->min_amount,
            'max_discount'=> $v->max_discount,
            'used'        => $v->used_count,
            'limit'       => $v->usage_limit,
            'valid_from'  => $v->valid_from?->format('Y-m-d'),
            'valid_until' => $v->valid_until?->format('Y-m-d'),
            'is_active'   => $v->is_active,
            'is_public'   => $v->is_public,
        ]);

        return json_encode(['count' => $data->count(), 'vouchers' => $data]);
    }

    private static function getSalesReport(array $args): string
    {
        $period = $args['period'] ?? 'month';

        [$start, $end, $prevStart, $prevEnd] = match ($period) {
            'today'      => [Carbon::today(), Carbon::now(), Carbon::yesterday(), Carbon::today()->subSecond()],
            'week'       => [Carbon::now()->startOfWeek(), Carbon::now(), Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()],
            'last_month' => [Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth(), Carbon::now()->subMonths(2)->startOfMonth(), Carbon::now()->subMonths(2)->endOfMonth()],
            default      => [Carbon::now()->startOfMonth(), Carbon::now(), Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()],
        };

        $current = Transaction::where('status', 'success')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('COUNT(*) as orders, SUM(amount) as revenue, SUM(profit) as profit, SUM(discount) as discounts_given')
            ->first();

        $previous = Transaction::where('status', 'success')
            ->whereBetween('created_at', [$prevStart, $prevEnd])
            ->selectRaw('COUNT(*) as orders, SUM(amount) as revenue, SUM(profit) as profit')
            ->first();

        $topProducts = Transaction::where('status', 'success')
            ->whereBetween('created_at', [$start, $end])
            ->join('products', 'transactions.product_id', '=', 'products.id')
            ->selectRaw('products.name, COUNT(*) as orders, SUM(transactions.profit) as profit')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('orders')
            ->limit(5)
            ->get();

        return json_encode([
            'period'   => $period,
            'range'    => [$start->format('Y-m-d'), $end->format('Y-m-d')],
            'current'  => $current,
            'previous' => $previous,
            'growth'   => [
                'revenue' => $previous->revenue > 0 ? round(($current->revenue - $previous->revenue) / $previous->revenue * 100, 1) : null,
                'orders'  => $previous->orders > 0 ? round(($current->orders - $previous->orders) / $previous->orders * 100, 1) : null,
            ],
            'top_products' => $topProducts,
        ]);
    }

    // ── WRITE TOOLS ─────────────────────────────────────────────────────────

    private static function createPromoCode(array $args): string
    {
        $code = strtoupper(trim($args['code']));

        if (Voucher::where('code', $code)->exists()) {
            return json_encode(['error' => "Kode '$code' sudah ada."]);
        }

        $voucher = Voucher::create([
            'code'        => $code,
            'type'        => $args['type'],
            'value'       => $args['value'],
            'min_amount'  => $args['min_amount'],
            'max_discount'=> $args['max_discount'] ?? null,
            'usage_limit' => $args['usage_limit'],
            'used_count'  => 0,
            'valid_from'  => isset($args['valid_from']) ? Carbon::parse($args['valid_from']) : Carbon::now(),
            'valid_until' => Carbon::parse($args['valid_until'])->endOfDay(),
            'is_active'   => true,
            'is_public'   => $args['is_public'] ?? false,
        ]);

        return json_encode(['success' => true, 'voucher_id' => $voucher->id, 'code' => $voucher->code]);
    }

    private static function deactivatePromo(array $args): string
    {
        $updated = Voucher::where('code', strtoupper(trim($args['code'])))->update(['is_active' => false]);

        if (! $updated) {
            return json_encode(['error' => "Kode '{$args['code']}' tidak ditemukan."]);
        }

        return json_encode(['success' => true, 'message' => "Kode {$args['code']} berhasil dinonaktifkan."]);
    }

    private static function updateProductPrice(array $args): string
    {
        $product = Product::find($args['product_id']);

        if (! $product) {
            return json_encode(['error' => "Produk ID {$args['product_id']} tidak ditemukan."]);
        }

        $oldPrice = $product->price_sell;
        $product->update(['price_sell' => $args['new_price_sell']]);

        return json_encode([
            'success'    => true,
            'product'    => $product->name,
            'old_price'  => $oldPrice,
            'new_price'  => $args['new_price_sell'],
        ]);
    }

    private static function sendNotification(array $args): string
    {
        $message = mb_substr($args['message'], 0, 500);

        BroadcastMessage::create([
            'message'   => $message,
            'is_active' => true,
        ]);

        return json_encode(['success' => true, 'message' => 'Broadcast berhasil dibuat dan aktif.']);
    }

    private static function adjustLoyaltyPoints(array $args): string
    {
        $user = User::find($args['user_id']);

        if (! $user) {
            return json_encode(['error' => "User ID {$args['user_id']} tidak ditemukan."]);
        }

        CoinTransaction::create([
            'user_id'      => $user->id,
            'amount'       => $args['delta'],
            'type'         => $args['delta'] > 0 ? 'admin_credit' : 'admin_debit',
            'description'  => $args['reason'],
            'reference_id' => null,
        ]);

        return json_encode([
            'success' => true,
            'user'    => $user->name,
            'delta'   => $args['delta'],
            'reason'  => $args['reason'],
        ]);
    }
}
