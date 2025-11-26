<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UpdateCustomersWithDefaultsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Update all existing customers with default notification preferences
        \DB::table('customers')->update([
            'email_notifications' => true,
            'sms_notifications' => true,
            'push_notifications' => false,
        ]);

        $this->command->info('All customers updated with default notification preferences!');
    }
}
