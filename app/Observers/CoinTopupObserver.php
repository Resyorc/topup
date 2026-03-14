<?php

namespace App\Observers;

use App\Events\CoinTopupStatusUpdated;
use App\Models\CoinTopup;

class CoinTopupObserver
{
    public function updated(CoinTopup $coinTopup): void
    {
        if ($coinTopup->wasChanged('status')) {
            CoinTopupStatusUpdated::dispatch($coinTopup);
        }
    }
}
