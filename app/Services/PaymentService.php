<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Ticket;
use App\Support\ApiResponse;
use Illuminate\Http\Exceptions\HttpResponseException;

class PaymentService
{
    public function process(Ticket $ticket, string $paymentMethod): Payment
    {
        $payment = Payment::query()->where('ticket_id', $ticket->id)->first();

        if (! $payment) {
            throw new HttpResponseException(
                ApiResponse::error('Payment record not found for this ticket', null, 404)
            );
        }

        if ($payment->status === 'completed') {
            throw new HttpResponseException(
                ApiResponse::error('Payment already completed', null, 409)
            );
        }

        $payment->update([
            'payment_method' => $paymentMethod,
            'status' => 'completed',
            'payment_date' => now(),
        ]);

        return $payment->fresh();
    }
}
