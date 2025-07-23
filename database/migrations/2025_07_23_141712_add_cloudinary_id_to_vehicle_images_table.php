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
        Schema::table('vehicle_images', function (Blueprint $table) {
            $table->string('cloudinary_id', 255)->nullable()->after('vehicle_id');
            $table->index('cloudinary_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_images', function (Blueprint $table) {
            $table->dropIndex(['cloudinary_id']);
            $table->dropColumn('cloudinary_id');
        });
    }
};
