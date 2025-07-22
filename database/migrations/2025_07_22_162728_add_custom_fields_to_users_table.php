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
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'manager', 'seller', 'viewer'])->default('viewer')->after('password');
            $table->string('phone', 20)->nullable()->after('role');
            $table->string('avatar_url', 500)->nullable()->after('phone');
            $table->foreignId('stand_id')->nullable()->constrained('stands')->onDelete('set null')->after('avatar_url');
            $table->decimal('commission_rate', 5, 2)->default(0)->after('stand_id');
            $table->boolean('is_active')->default(true)->after('commission_rate');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['stand_id']);
            $table->dropColumn([
                'role',
                'phone',
                'avatar_url',
                'stand_id',
                'commission_rate',
                'is_active',
                'last_login_at',
                'deleted_at'
            ]);
        });
    }
};
