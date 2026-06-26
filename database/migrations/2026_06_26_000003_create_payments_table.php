<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            // restrictOnDelete: an order with payments cannot be deleted at the DB level,
            // mirroring the business rule enforced in the service layer.
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->enum('status', ['pending', 'successful', 'failed'])->default('pending');
            $table->string('method');
            $table->decimal('amount', 12, 2);
            $table->string('transaction_reference')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
