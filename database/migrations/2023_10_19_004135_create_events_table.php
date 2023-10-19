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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('event_name');
            $table->time('start_time');
            $table->time('end_time');
            $table->date('date');
            $table->decimal('price', 10, 2);
            $table->json('gallery');
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->string('address');
            $table->string('category')->nullable();
            $table->integer('attendance_limit');
            $table->json('gears')->nullable();
            $table->string('itinerary')->nullable();
            $table->text('event_description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
