<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Campus Lost & Found database seeding...');

        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            RolePermissionSeeder::class,
            ReferenceDataSeeder::class,
            UserSeeder::class,
            ItemSeeder::class,
            ClaimSeeder::class,
            AuditLogSeeder::class,
        ]);

        $this->command->info('Database seeding completed successfully!');
        $this->command->info('');
        $this->command->info('===== DEFAULT SYSTEM ACCOUNTS =====');
        $this->command->info('Admin   : admin@mwangatech.ac.tz / Admin@12345');
        $this->command->info('Officer : office@mwangatech.ac.tz / Officer@12345');
    }
}
