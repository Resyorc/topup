<?php

if (!function_exists('maskPhoneNumber')) {
    function maskPhoneNumber(string $phone): string
    {
        return substr($phone, 0, 4) . str_repeat('*', strlen($phone) - 8) . substr($phone, -4);
    }
}

if (!function_exists('formatRupiah')) {
    function formatRupiah(float $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
}