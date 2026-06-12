<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Ticket;
use App\Support\ApiResponse;
use Illuminate\Http\Exceptions\HttpResponseException;

class PaymentService
{
    public function __construct(
        private readonly SOAPAuditService $soapAuditService,
        private readonly RabbitMQService $rabbitMQService,
    ) {}

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

        // Update local status
        $payment->update([
            'payment_method' => $paymentMethod,
            'status' => 'completed',
            'payment_date' => now(),
        ]);

        $updatedPayment = $payment->fresh();

        // 1. SOAP XML Audit (Transaksi Kritis)
        $auditData = [
            'ticket_id' => $ticket->id,
            'cust_name' => $ticket->Cust_Name,
            'amount' => (float) $updatedPayment->amount,
            'payment_method' => $updatedPayment->payment_method,
            'payment_date' => $updatedPayment->payment_date ? $updatedPayment->payment_date->toIso8601String() : now()->toIso8601String(),
        ];
        
        $receiptNumber = $this->soapAuditService->sendAuditLog('PaymentProcessed', $auditData);
        
        if ($receiptNumber) {
            $updatedPayment->update([
                'soap_receipt_number' => $receiptNumber
            ]);
        }

        // 2. Publish event ke RabbitMQ
        $eventPayload = [
            'event' => 'TicketPaymentCompleted',
            'ticket_id' => $ticket->id,
            'cust_name' => $ticket->Cust_Name,
            'route_id' => $ticket->route_id,
            'seat_number' => $ticket->seat_number,
            'amount' => (float) $updatedPayment->amount,
            'receipt_number' => $receiptNumber ?? 'PENDING',
            'timestamp' => now()->toIso8601String()
        ];

        $this->rabbitMQService->publishEvent('ticket.payment.completed', $eventPayload);

        return $updatedPayment->fresh();
    }
}
