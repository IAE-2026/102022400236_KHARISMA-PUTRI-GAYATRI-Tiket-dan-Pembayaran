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

    public function test_rejects_wrong_iae_key(): void
    {
        $this->withHeader('X-IAE-KEY', '123456789')
            ->getJson('/api/v1/tickets')
            ->assertUnauthorized()
            ->assertJsonPath('status', 'error');
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

    public function test_rest_endpoints_match_required_status_and_wrappers(): void
    {
        $headers = ['X-IAE-KEY' => self::IAE_KEY];

        $this->withHeaders($headers)
            ->getJson('/api/v1/tickets')
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure(['status', 'message', 'data']);

        $this->withHeaders($headers)
            ->getJson('/api/v1/tickets/999')
            ->assertNotFound()
            ->assertJsonPath('status', 'error')
            ->assertJsonStructure(['status', 'message', 'data']);

        $this->withHeaders($headers)
            ->postJson('/api/v1/tickets', [
                'cust_name' => 'Dewi Lestari',
                'route_id' => 7,
                'seat_number' => 'D10',
                'price' => 175000,
                'payment_method' => 'QRIS',
            ])
            ->assertCreated()
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure(['status', 'message', 'data' => ['id', 'cust_name', 'payment']]);
    }

    public function test_swagger_ui_and_spec_are_available(): void
    {
        $this->get('/api/documentation')->assertOk();

        $this->getJson('/docs')
            ->assertOk()
            ->assertJsonPath('paths./api/v1/tickets.get.operationId', 'listTickets')
            ->assertJsonPath('components.securitySchemes.IAEKey.name', 'X-IAE-KEY');
    }

    public function test_graphql_endpoint_allows_introspection(): void
    {
        $this->withHeader('X-IAE-KEY', self::IAE_KEY)
            ->postJson('/graphql', [
                'query' => '{ __schema { queryType { name } } }',
            ])
            ->assertOk()
            ->assertJsonPath('data.__schema.queryType.name', 'Query');
    }

    public function test_graphql_endpoint_requires_iae_key(): void
    {
        $this->postJson('/graphql', [
            'query' => '{ __schema { queryType { name } } }',
        ])
            ->assertUnauthorized()
            ->assertJsonPath('status', 'error')
            ->assertJsonStructure(['status', 'message', 'data', 'errors']);
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
