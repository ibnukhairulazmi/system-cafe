<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('code', 12)->unique();
            $table->string('name', 100);
            $table->string('phone', 20);
            $table->date('reserved_date')->index();
            $table->time('reserved_time');
            $table->unsignedTinyInteger('party_size');
            $table->text('notes')->nullable();
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('payment_method', 20)->nullable();
            $table->string('payment_status', 20)->default('unpaid');
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->string('status', 20)->default('awaiting_payment')->index();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->text('staff_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
