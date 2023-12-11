<?php

namespace Database\Seeders;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a Super Admin
        User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => bcrypt('password12@!'),
            'email_verified' => true,
            'role' => 'super_admin',
            'phone_number' => '111-222-3334',
            'color' => '#565656'

        ]);
        // Create a Guide
        User::factory()->create([
            'name' => 'Guide User',
            'email' => 'guide@example.com',
            'password' => bcrypt('password12@!'),
            'email_verified' => true,
            'company_email' => 'guide_email@example.com',
            'role' => 'guide',
            'phone_number' => '222-333-4445',
            'is_approved' => true,
            'country' => 'CA',
            'guide_awards' => '["World\'s Ice Climber of the year 2022", "Canada\'s Rock Climber of the year 2023"]',
            'guide_terms' => '{"key": "guide_terms", "url": "docs/XuFnOFjaoYfn1e1gdwYkgdqtlgI7KAdJZ8kZ6MdX.pdf", "name": "sample-terms-conditions-agreement.pdf"}',
            'stripe_account_id' => 'acct_1OCwBoPv3lldH5qB',
            'charges_enabled' => true,
            'payouts_enabled' => true,
            'details_submitted' => true,
            'color' => '#7854da'

        ]);
         // Create a Climber
         User::factory()->create([
            'name' => 'Climber User',
            'email' => 'climber@example.com',
            'password' => bcrypt('password12@!'),
            'email_verified' => true,
            'role' => 'climber',
            'phone_number' => '222-333-4445',
            'gender' => 'male',
            'dob' => '1990-06-10',
            'color' => '#f2ba21'
        ]);
    }
}
