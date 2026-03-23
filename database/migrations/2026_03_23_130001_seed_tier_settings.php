<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $settings = [
            'tier_multiplier_bronze'   => '1.0',
            'tier_multiplier_silver'   => '1.25',
            'tier_multiplier_gold'     => '1.5',
            'tier_multiplier_platinum' => '2.0',
            'tier_threshold_silver'    => '500000',
            'tier_threshold_gold'      => '2000000',
            'tier_threshold_platinum'  => '10000000',
        ];

        foreach ($settings as $key => $value) {
            DB::table('settings')->updateOrInsert(['key' => $key], ['value' => $value]);
        }
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', [
            'tier_multiplier_bronze', 'tier_multiplier_silver',
            'tier_multiplier_gold', 'tier_multiplier_platinum',
            'tier_threshold_silver', 'tier_threshold_gold', 'tier_threshold_platinum',
        ])->delete();
    }
};
