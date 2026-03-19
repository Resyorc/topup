<?php

use App\Models\Voucher;
use App\Services\VoucherService;
use Illuminate\Support\Facades\DB;

// ── Helper ────────────────────────────────────────────────────────────────────

function makeVoucher(array $overrides = []): Voucher
{
    return Voucher::create(array_merge([
        'code'        => 'TESTV' . strtoupper(substr(uniqid(), -4)),
        'type'        => 'flat',
        'value'       => 10000,
        'min_amount'  => 0,
        'max_discount' => null,
        'usage_limit' => null,
        'used_count'  => 0,
        'is_active'   => true,
        'is_public'   => true,
    ], $overrides));
}

// ── WHITE-BOX: validateAndClaim() ────────────────────────────────────────────

describe('VoucherService::validateAndClaim', function () {

    test('[WHITE-BOX] klaim berhasil mengembalikan diskon dan increment used_count', function () {
        $voucher = makeVoucher(['code' => 'FLAT10K', 'value' => 10000, 'usage_limit' => 5]);
        $service = new VoucherService;

        $discount = DB::transaction(fn () => $service->validateAndClaim('FLAT10K', 50000));

        expect($discount)->toBe(10000);
        expect($voucher->fresh()->used_count)->toBe(1);
    });

    test('[WHITE-BOX] klaim voucher percent mengembalikan kalkulasi yang benar', function () {
        makeVoucher(['code' => 'PCT10', 'type' => 'percent', 'value' => 10]);
        $service = new VoucherService;

        $discount = DB::transaction(fn () => $service->validateAndClaim('PCT10', 100000));

        expect($discount)->toBe(10000);
    });

    test('[WHITE-BOX] gagal jika voucher tidak ditemukan', function () {
        $service = new VoucherService;

        expect(fn () => DB::transaction(fn () => $service->validateAndClaim('TIDAKADA', 50000)))
            ->toThrow(\Exception::class, 'Kode voucher tidak ditemukan.');
    });

    test('[WHITE-BOX] gagal jika voucher tidak aktif', function () {
        makeVoucher(['code' => 'NONAKTIF', 'is_active' => false]);
        $service = new VoucherService;

        expect(fn () => DB::transaction(fn () => $service->validateAndClaim('NONAKTIF', 50000)))
            ->toThrow(\Exception::class, 'Kode voucher tidak ditemukan.');
    });

    test('[WHITE-BOX] gagal jika usage_limit sudah penuh', function () {
        makeVoucher(['code' => 'HABIS', 'usage_limit' => 3, 'used_count' => 3]);
        $service = new VoucherService;

        expect(fn () => DB::transaction(fn () => $service->validateAndClaim('HABIS', 50000)))
            ->toThrow(\Exception::class, 'Voucher sudah habis digunakan.');
    });

    test('[WHITE-BOX] gagal jika voucher sudah kadaluarsa', function () {
        makeVoucher(['code' => 'EXPIRED', 'valid_until' => now()->subDay()]);
        $service = new VoucherService;

        expect(fn () => DB::transaction(fn () => $service->validateAndClaim('EXPIRED', 50000)))
            ->toThrow(\Exception::class, 'Voucher sudah kadaluarsa.');
    });

    test('[WHITE-BOX] gagal jika voucher belum aktif (valid_from di masa depan)', function () {
        makeVoucher(['code' => 'BELUMAKTIF', 'valid_from' => now()->addDay()]);
        $service = new VoucherService;

        expect(fn () => DB::transaction(fn () => $service->validateAndClaim('BELUMAKTIF', 50000)))
            ->toThrow(\Exception::class, 'Voucher belum aktif.');
    });

    test('[WHITE-BOX] gagal jika amount di bawah min_amount', function () {
        makeVoucher(['code' => 'MINAMNT', 'min_amount' => 100000]);
        $service = new VoucherService;

        expect(fn () => DB::transaction(fn () => $service->validateAndClaim('MINAMNT', 50000)))
            ->toThrow(\Exception::class);
    });

    test('[WHITE-BOX] max_discount membatasi diskon persen', function () {
        makeVoucher([
            'code'        => 'MAXCAP',
            'type'        => 'percent',
            'value'       => 50,
            'max_discount' => 20000,
        ]);
        $service = new VoucherService;

        // 50% dari 100000 = 50000, tapi max_discount = 20000
        $discount = DB::transaction(fn () => $service->validateAndClaim('MAXCAP', 100000));

        expect($discount)->toBe(20000);
    });

    // ── GRAY-BOX: race condition simulation ──────────────────────────────────

    test('[GRAY-BOX] klaim kedua gagal setelah kuota habis akibat klaim pertama', function () {
        makeVoucher(['code' => 'LIMIT1', 'usage_limit' => 1, 'used_count' => 0]);
        $service = new VoucherService;

        // Klaim pertama berhasil
        DB::transaction(fn () => $service->validateAndClaim('LIMIT1', 50000));

        // Klaim kedua harus gagal — simulates second concurrent request after first committed
        expect(fn () => DB::transaction(fn () => $service->validateAndClaim('LIMIT1', 50000)))
            ->toThrow(\Exception::class, 'Voucher sudah habis digunakan.');

        // used_count tetap 1, tidak melebihi limit
        expect(Voucher::where('code', 'LIMIT1')->first()->used_count)->toBe(1);
    });

    test('[GRAY-BOX] kode voucher case-insensitive', function () {
        makeVoucher(['code' => 'CASECODE', 'value' => 5000]);
        $service = new VoucherService;

        $discount = DB::transaction(fn () => $service->validateAndClaim('casecode', 50000));

        expect($discount)->toBe(5000);
    });
});
