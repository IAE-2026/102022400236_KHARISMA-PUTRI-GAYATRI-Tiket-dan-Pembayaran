<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            // Hubungkan dengan tabel tickets
            $table->unsignedBigInteger('ticket_id');
            $table->decimal('amount', 8, 2);
            $table->enum('payment_method', ['credit_card', 'bank_transfer', 'QRIS'])->default('credit_card');
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->timestamp('payment_date')->nullable();
            $table->foreign('ticket_id')->references('id')->on('tickets');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
