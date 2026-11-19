<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->foreignId('cafe_table_id')->nullable()->after('party_size')
                ->constrained('cafe_tables')->nullOnDelete();
            $table->timestamp('hold_expires_at')->nullable();
            $table->string('gateway_order_id', 64)->nullable()->unique();
            $table->string('snap_token')->nullable();
            $table->timestamp('checked_in_at')->nullable();

            $table->index(['cafe_table_id', 'reserved_date']);
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cafe_table_id');
            $table->dropColumn(['hold_expires_at', 'gateway_order_id', 'snap_token', 'checked_in_at']);
        });
    }
};