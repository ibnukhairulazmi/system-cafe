<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('order_type', 20)->default('dine_in')->after('shift_id');
            $table->foreignId('cafe_table_id')->nullable()->after('order_type')
                ->constrained('cafe_tables')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cafe_table_id');
            $table->dropColumn('order_type');
        });
    }
};
