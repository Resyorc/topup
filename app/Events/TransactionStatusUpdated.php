<?php

namespace App\Events;

use App\Models\Transaction;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransactionStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Transaction $transaction)
    {
        $this->transaction->loadMissing('product');
    }

    public function broadcastOn(): array
    {
        $channels = [
            new Channel('invoice.'.$this->transaction->invoice_id),
        ];

        if ($this->transaction->user_id) {
            $channels[] = new PrivateChannel('transactions.'.$this->transaction->user_id);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'InvoiceStatusUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'invoice_id' => $this->transaction->invoice_id,
            'status' => $this->transaction->status,
            'payment_status' => $this->transaction->payment_status,
            'product_name' => $this->transaction->product->name ?? '-',
        ];
    }
}
