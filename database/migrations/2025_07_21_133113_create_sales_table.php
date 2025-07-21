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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->onDelete('cascade');
            $table->foreignId('seller_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('stand_id')->constrained()->onDelete('cascade');
            $table->decimal('sale_price', 10, 2);
            $table->decimal('commission_amount', 10, 2)->default(0);
            $table->enum('payment_method', ['cash', 'financing', 'lease', 'trade_in'])->default('cash');
            $table->string('customer_name', 255);
            $table->string('customer_email', 255)->nullable();
            $table->string('customer_phone', 20)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('sold_at');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('vehicle_id');
            $table->index('seller_id');
            $table->index('stand_id');
            $table->index('sale_price');
            $table->index('payment_method');
            $table->index('sold_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
