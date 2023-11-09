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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('event_id');
            $table->unsignedBigInteger('guide_id');
            $table->string('event_name');
            $table->string('total_price');
            $table->string('payment_session_id')->nullable();
            $table->string('receipt_no');
            $table->boolean('paid')->default(false);
            $table->boolean('accepted')->default(false);
            $table->boolean('relist')->default(false);
            $table->boolean('climber_delete')->default(false);
            $table->boolean('guide_delete')->default(false);
            $table->boolean('admin_delete')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
