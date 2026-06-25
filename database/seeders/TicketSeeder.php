<?php

namespace Database\Seeders;

use App\Models\Payment;
use App\Models\Ticket;
use Illuminate\Database\Seeder;

class TicketSeeder extends Seeder
{
    public function run(): void
    {
        $ticket = Ticket::query()->updateOrCreate(
            [
                'route_id' => 1,
                'seat_number' => 'A12',
            ],
            [
                'Cust_Name' => 'Budi Santoso',
                'status' => 'booked',
                'price' => 150000,
            ]
        );

        Payment::query()->updateOrCreate(
            ['ticket_id' => $ticket->id],
            [
                'amount' => 150000,
                'payment_method' => 'credit_card',
                'status' => 'pending',
                'payment_date' => null,
            ]
        );

        $paid = Ticket::query()->updateOrCreate(
            [
                'route_id' => 2,
                'seat_number' => 'B05',
            ],
            [
                'Cust_Name' => 'Siti Aminah',
                'status' => 'booked',
                'price' => 200000,
            ]
        );

        Payment::query()->updateOrCreate(
            ['ticket_id' => $paid->id],
            [
                'amount' => 200000,
                'payment_method' => 'QRIS',
                'status' => 'completed',
                'payment_date' => now(),
            ]
        );
    }
}
