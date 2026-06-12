<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed default roles
        \App\Models\Role::firstOrCreate(['name' => 'warga']);
        \App\Models\Role::firstOrCreate(['name' => 'admin']);

        $this->call(TicketSeeder::class);
    }
}
