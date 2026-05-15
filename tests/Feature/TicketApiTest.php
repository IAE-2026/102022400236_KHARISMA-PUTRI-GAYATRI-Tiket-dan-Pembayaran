<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketApiTest extends TestCase
{
    use RefreshDatabase;

    private const IAE_KEY = '102022400236';

    public function test_requires_iae_key(): void
    {
        $this->getJson('/api/v1/tickets')->assertUnauthorized();
    }

    public function test_four_endpoint_happy_path(): void
    {
        $ticket = Ticket::query()->create([
            'Cust_Name' => 'Budi Santoso',
            'route_id' => 1,
            'seat_number' => 'A12',
            'status' => 'booked',
            'price' => 150000,
        ]);

        Payment::query()->create([
            'ticket_id' => $ticket->id,
            'amount' => 150000,
            'payment_method' => 'credit_card',
            'status' => 'pending',
        ]);

        $headers = ['X-IAE-KEY' => self::IAE_KEY];

        $this->withHeaders($headers)
            ->getJson('/api/v1/tickets')
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure(['status', 'message', 'data']);

        $this->withHeaders($headers)
            ->getJson("/api/v1/tickets/{$ticket->id}")
            ->assertOk()
            ->assertJsonPath('data.cust_name', 'Budi Santoso');

        $this->withHeaders($headers)
            ->postJson("/api/v1/tickets/{$ticket->id}/payments", [
                'payment_method' => 'bank_transfer',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'completed');

        $this->withHeaders($headers)
            ->postJson("/api/v1/tickets/{$ticket->id}/send")
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure(['data' => ['e_ticket_code', 'ticket_id']]);
    }

    public function test_e_ticket_requires_completed_payment(): void
    {
        $ticket = Ticket::query()->create([
            'Cust_Name' => 'Ana',
            'route_id' => 1,
            'seat_number' => 'C01',
            'status' => 'booked',
            'price' => 100000,
        ]);

        Payment::query()->create([
            'ticket_id' => $ticket->id,
            'amount' => 100000,
            'payment_method' => 'QRIS',
            'status' => 'pending',
        ]);

        $this->withHeader('X-IAE-KEY', self::IAE_KEY)
            ->postJson("/api/v1/tickets/{$ticket->id}/send")
            ->assertStatus(402);
    }
}
