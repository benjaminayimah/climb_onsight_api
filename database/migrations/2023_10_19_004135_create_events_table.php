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
            $table->date('start_date');
            $table->date('end_date');
            $table->json('price');
            $table->json('gallery');
            $table->enum('event_type', ['public', 'private'])->default('public'); // new
            $table->json('event_terms'); //new
            $table->string('event_duration'); //new
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->string('address');
            $table->string('category')->nullable();
            $table->integer('attendance_limit');
            $table->unsignedInteger('limit_count')->default(0);
            $table->json('climber_gears')->nullable(); // new
            $table->json('guide_gears')->nullable(); // new
            $table->json('experience_required')->nullable(); // new
            $table->string('itinerary')->nullable();
            $table->text('event_description')->nullable();
            $table->json('faqs')->nullable();
            $table->enum('repeat_at', ['daily', 'weekly', 'weekdays', 'weekends', 'monthly'])->nullable()->default('daily'); // new
            $table->string('color_class')->nullable();
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
