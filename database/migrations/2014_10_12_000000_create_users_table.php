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
            $table->string('email')->unique()->nullable();
            $table->boolean('email_verified')->default(false);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('phone_number')->nullable();
            $table->string('country')->nullable();
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
            $table->string('company_email')->nullable();
            $table->json('guide_insurance')->nullable();
            $table->json('guide_certificate')->nullable();
            $table->json('guide_terms')->nullable();
            $table->string('customer_reviews')->nullable();
            $table->json('guide_awards')->nullable();
            $table->json('guide_experience')->nullable();
            $table->json('referees')->nullable();
            $table->json('gallery')->nullable();
            $table->boolean('is_approved')->default(false);

            // stripe account
            $table->string('stripe_account_id')->nullable();
            $table->boolean('charges_enabled')->default(false);
            $table->boolean('payouts_enabled')->default(false);
            $table->boolean('details_submitted')->default(false);


            $table->json('permissions')->nullable(); 

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
