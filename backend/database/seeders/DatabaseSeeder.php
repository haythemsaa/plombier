<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed services first (categories and services)
        $this->call(ServiceSeeder::class);

        // Then seed users, providers, and addresses
        $this->call(UserSeeder::class);

        $this->command->info('✅ Database seeded successfully!');
        $this->command->info('');
        $this->command->info('🔑 Test Accounts:');
        $this->command->info('');
        $this->command->info('Client:');
        $this->command->info('  Email: client@servicehub.tn');
        $this->command->info('  Phone: +216 98 123 456');
        $this->command->info('  Password: password');
        $this->command->info('');
        $this->command->info('Providers:');
        $this->command->info('  plombier@servicehub.tn / password');
        $this->command->info('  electricien@servicehub.tn / password');
        $this->command->info('  menage@servicehub.tn / password');
        $this->command->info('  clim@servicehub.tn / password');
        $this->command->info('  babysitting@servicehub.tn / password');
    }
}
