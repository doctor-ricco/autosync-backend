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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('email', 255)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password', 255);
            $table->enum('role', ['admin', 'manager', 'seller', 'viewer'])->default('viewer');
            $table->string('phone', 20)->nullable();
            $table->string('avatar_url', 500)->nullable();
            $table->foreignId('stand_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('commission_rate', 5, 2)->default(0); // Taxa de comissão do vendedor
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('email');
            $table->index('role');
            $table->index('is_active');
            $table->index('stand_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
