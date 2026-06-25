<?php

namespace App\Services;

use App\Http\Resources\PaymentResource;
use App\Models\Ticket;
use App\Support\ApiResponse;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Str;

class ETicketService
{
    public function issue(Ticket $ticket): array
    {
        $ticket->loadMissing('payment');

        if (! $ticket->payment) {
            throw new HttpResponseException(
                ApiResponse::error('Payment record not found for this ticket', null, 404)
            );
        }

        if ($ticket->payment->status !== 'completed') {
            throw new HttpResponseException(
                ApiResponse::error('E-Ticket can only be issued after payment is completed', null, 402)
            );
        }

        return [
            'ticket_id' => $ticket->id,
            'e_ticket_code' => $this->generateCode($ticket),
            'cust_name' => $ticket->Cust_Name,
            'route_id' => $ticket->route_id,
            'seat_number' => $ticket->seat_number,
            'price' => (float) $ticket->price,
            'issued_at' => now()->toIso8601String(),
            'payment' => PaymentResource::make($ticket->payment),
        ];
    }

    private function generateCode(Ticket $ticket): string
    {
        return 'ETK-'.str_pad((string) $ticket->id, 6, '0', STR_PAD_LEFT).'-'.strtoupper(Str::random(6));
    }
}
