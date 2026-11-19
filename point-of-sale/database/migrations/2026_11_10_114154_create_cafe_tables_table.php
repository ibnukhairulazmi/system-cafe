<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cafe_tables', function (Blueprint $table) {
            $table->id();
            $table->string('name', 30)->unique();
            $table->string('area', 30)->nullable();     
            $table->unsignedTinyInteger('capacity');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_occupied')->default(false); 
            $table->timestamp('occupied_at')->nullable();
            $table->foreignId('occupied_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cafe_tables');
    }
};