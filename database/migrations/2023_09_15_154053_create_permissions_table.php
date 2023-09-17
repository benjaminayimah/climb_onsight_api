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
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->boolean('events')->default(false);
            $table->boolean('climbers')->default(false);
            $table->boolean('guides')->default(false);
            $table->boolean('stats')->default(false);
            $table->boolean('locations')->default(false);
            $table->boolean('payments')->default(false);
            $table->boolean('merchandise')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
