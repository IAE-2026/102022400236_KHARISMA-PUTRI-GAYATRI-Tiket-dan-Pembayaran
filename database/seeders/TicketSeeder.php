<?php

namespace Database\Seeders;

use App\Models\Payment;
use App\Models\Ticket;
use Illuminate\Database\Seeder;

class TicketSeeder extends Seeder
{
    public function run(): void
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

        $paid = Ticket::query()->create([
            'Cust_Name' => 'Siti Aminah',
            'route_id' => 2,
            'seat_number' => 'B05',
            'status' => 'booked',
            'price' => 200000,
        ]);

        Payment::query()->create([
            'ticket_id' => $paid->id,
            'amount' => 200000,
            'payment_method' => 'QRIS',
            'status' => 'completed',
            'payment_date' => now(),
        ]);
    }
}
