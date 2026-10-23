<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('lot_number', 100);
            $table->date('expiration_date');
            $table->unsignedInteger('quantity')->default(0);
            $table->timestamps();

            // Índice compuesto único obligatorio según el examen
            $table->unique(['product_id', 'lot_number']);
            $table->index('expiration_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lots');
    }
};