<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Ticket;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Services\SOAPAuditService;
use App\Services\RabbitMQService;
use App\Http\Middleware\VerifyFederatedJWT;

class TicketApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Mock the VerifyFederatedJWT middleware
        $this->app->instance(VerifyFederatedJWT::class, new class {
            public function handle($request, $next)
            {
                $token = $request->bearerToken();
                if (!$token) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Akses ditolak: Token JWT tidak ditemukan pada Header'
                    ], 401);
                }

                if ($token === 'invalid-token') {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Token tidak valid'
                    ], 401);
                }

                $role = Role::firstOrCreate(['name' => 'warga']);
                $user = User::firstOrCreate(
                    ['email' => 'warga37@ktp.iae.id'],
                    [
                        'name' => 'warga37',
                        'password' => \Illuminate\Support\Facades\Hash::make('password'),
                        'role_id' => $role->id,
                    ]
                );

                \Illuminate\Support\Facades\Auth::setUser($user);
                $request->attributes->add([
                    'user_email' => 'warga37@ktp.iae.id',
                    'user_role' => 'warga',
                    'user_id' => $user->id,
                ]);

                return $next($request);
            }
        });

        // 2. Mock SOAP Audit Service
        $mockSoap = $this->createMock(SOAPAuditService::class);
        $mockSoap->method('sendAuditLog')->willReturn('IAE-LOG-2026-TEST1234');
        $this->app->instance(SOAPAuditService::class, $mockSoap);

        // 3. Mock RabbitMQ Service
        $mockMq = $this->createMock(RabbitMQService::class);
        $mockMq->method('publishEvent')->willReturn(true);
        $this->app->instance(RabbitMQService::class, $mockMq);
    }

    public function test_requires_jwt_token(): void
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

        $headers = ['Authorization' => 'Bearer valid-sso-token'];

        // 1. Get List
        $this->withHeaders($headers)
            ->getJson('/api/v1/tickets')
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure(['status', 'message', 'data']);

        // 2. Get Detail
        $this->withHeaders($headers)
            ->getJson("/api/v1/tickets/{$ticket->id}")
            ->assertOk()
            ->assertJsonPath('data.cust_name', 'Budi Santoso');

        // 3. Process Payment
        $this->withHeaders($headers)
            ->postJson("/api/v1/tickets/{$ticket->id}/payments", [
                'payment_method' => 'bank_transfer',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.soap_receipt_number', 'IAE-LOG-2026-TEST1234');

        // Verify database state
        $this->assertDatabaseHas('payments', [
            'ticket_id' => $ticket->id,
            'status' => 'completed',
            'soap_receipt_number' => 'IAE-LOG-2026-TEST1234'
        ]);

        // 4. Send E-Ticket
        $this->withHeaders($headers)
            ->postJson("/api/v1/tickets/{$ticket->id}/send")
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure(['data' => ['e_ticket_code', 'ticket_id']]);
    }

    public function test_can_create_ticket_with_pending_payment(): void
    {
        $this->withHeaders(['Authorization' => 'Bearer valid-sso-token'])
            ->postJson('/api/v1/tickets', [
                'cust_name' => 'Khai',
                'route_id' => 37,
                'seat_number' => 'D07',
                'price' => 175000,
            ])
            ->assertCreated()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.cust_name', 'Khai')
            ->assertJsonPath('data.payment.status', 'pending');

        $this->assertDatabaseHas('tickets', [
            'Cust_Name' => 'Khai',
            'route_id' => 37,
            'seat_number' => 'D07',
        ]);

        $this->assertDatabaseHas('payments', [
            'amount' => 175000,
            'status' => 'pending',
        ]);
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

        $this->withHeaders(['Authorization' => 'Bearer valid-sso-token'])
            ->postJson("/api/v1/tickets/{$ticket->id}/send")
            ->assertStatus(402);
    }
}
