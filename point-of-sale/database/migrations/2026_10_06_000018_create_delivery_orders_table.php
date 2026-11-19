<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_orders', function (Blueprint $table) {
            $table->id();
            $table->string('code', 16)->unique();
            $table->string('customer_name', 100);
            $table->string('customer_phone', 20);
            $table->text('delivery_address');
            $table->string('landmark', 255)->nullable();
            $table->string('delivery_tier', 20)->default('saving');
            $table->decimal('delivery_fee', 12, 2)->default(0);
            $table->decimal('subtotal_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->date('delivery_date')->index();
            $table->string('delivery_time', 10)->default('ASAP');
            $table->boolean('is_asap')->default(true);
            $table->string('payment_method', 20)->default('qris');
            $table->string('payment_status', 20)->default('unpaid');
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->text('customer_note')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->text('staff_note')->nullable();
            $table->timestamps();
        });

        Schema::create('delivery_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_order_id')->constrained('delivery_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('product_name', 150);
            $table->decimal('price', 12, 2);
            $table->unsignedSmallInteger('quantity');
            $table->decimal('subtotal', 12, 2);
            $table->string('note', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_order_items');
        Schema::dropIfExists('delivery_orders');
    }
};

