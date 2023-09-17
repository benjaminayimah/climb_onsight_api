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
            $table->string('name');
            $table->string('email')->unique();
            $table->boolean('email_verified')->default(false);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('phone_number');
            $table->enum('role', ['super_admin', 'admin', 'guide', 'climber'])->default('climber');
            $table->string('profile_picture')->nullable();

            //climber specific feilds
            $table->date('dob')->nullable();
            $table->string('gender')->nullable();
            $table->text('bio')->nullable();
            $table->json('activities')->nullable();
            $table->json('skills')->nullable();
            $table->json('new_skills')->nullable();

            // Add fields specific to Guides
            $table->text('customer_reviews')->nullable();
            $table->string('referee_name')->nullable();
            $table->string('referee_email')->nullable();
            $table->string('referee_phone_number')->nullable();
            $table->string('guide_insurance')->nullable();
            $table->string('guide_certificate')->nullable();
            $table->json('gallery')->nullable();
            $table->boolean('is_approved')->default(false);

            $table->rememberToken();
            $table->timestamps();
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