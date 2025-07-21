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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stand_id')->constrained()->onDelete('cascade');
            $table->string('reference', 50)->unique();
            $table->string('brand', 100);
            $table->string('model', 100);
            $table->integer('year');
            $table->integer('mileage');
            $table->enum('fuel_type', ['gasoline', 'diesel', 'hybrid', 'electric', 'lpg']);
            $table->enum('transmission', ['manual', 'automatic', 'semi_automatic']);
            $table->decimal('engine_size', 3, 1)->nullable();
            $table->integer('power_hp')->nullable();
            $table->integer('doors')->default(5);
            $table->integer('seats')->default(5);
            $table->string('color', 50);
            $table->decimal('price', 10, 2);
            $table->decimal('original_price', 10, 2)->nullable();
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->text('description')->nullable();
            $table->json('features')->nullable();
            $table->enum('status', ['available', 'sold', 'reserved', 'maintenance'])->default('available');
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_new')->default(false);
            $table->integer('views_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('stand_id');
            $table->index('brand');
            $table->index('model');
            $table->index('year');
            $table->index('price');
            $table->index('status');
            $table->index('is_featured');
            $table->index('views_count');
            $table->index('reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
