<?php

namespace App\Events;

use App\Models\CoinTopup;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CoinTopupStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly CoinTopup $coinTopup) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('invoice.'.$this->coinTopup->invoice_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'InvoiceStatusUpdated';
    }

    public function broadcastWith(): array
    {
        $status = match ($this->coinTopup->status) {
            'paid' => 'success',
            'expired' => 'failed',
            default => $this->coinTopup->status,
        };

        $paymentStatus = match ($this->coinTopup->status) {
            'paid' => 'paid',
            'expired' => 'expired',
            default => $this->coinTopup->status,
        };

        return [
            'invoice_id' => $this->coinTopup->invoice_id,
            'status' => $status,
            'payment_status' => $paymentStatus,
            'product_name' => 'Krysta Coins',
        ];
    }
}
