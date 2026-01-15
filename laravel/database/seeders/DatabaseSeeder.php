<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Database seeder - Orchestrates all seeders.
 *
 * Run with: php artisan db:seed
 * Run specific seeder: php artisan db:seed --class=CoordinateMappingSeeder
 */
final class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run(): void
    {
        $this->call([
            BitRegistrySeeder::class,
            CoordinateMappingSeeder::class,
            ProjectionSeeder::class,
        ]);
    }
}
