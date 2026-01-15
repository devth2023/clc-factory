<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Coordinate mapping seeder - Seeds the coordinate_mappings table.
 *
 * Creates the three-layer registry entries from master_registry.yaml data.
 *
 * Layer 1: Glossary (human labels)
 * Layer 2: Coordinate Address (XXXX.YYYY@ format)
 * Layer 3: Bitmask Policy (the source of truth)
 */
final class CoordinateMappingSeeder extends Seeder
{
    /**
     * Seed the coordinate_mappings table.
     *
     * @return void
     */
    public function run(): void
    {
        $coordinates = [
            // From master_registry.yaml - COORD_X101
            [
                'coordinate_key' => 'COORD_X101',
                'label' => 'User_Profile_Name',
                'description' => 'ชื่อจริงสำหรับแสดงผล',
                'seo_keywords' => json_encode(['user', 'profile', 'name']),
                'schema_type' => 'Person',
                'coordinate_address' => '1010.01010@',
                'bitmask_policy' => 0x0001,
                'version' => 1,
                'is_active' => true,
            ],

            // From master_registry.yaml - COORD_X102
            [
                'coordinate_key' => 'COORD_X102',
                'label' => 'Dashboard_Stats',
                'description' => 'สถิติแดชบอร์ดสำหรับผู้ใช้',
                'seo_keywords' => json_encode(['dashboard', 'stats', 'analytics']),
                'schema_type' => 'AggregateRating',
                'coordinate_address' => '1020.02020@',
                'bitmask_policy' => 0x0002,
                'version' => 1,
                'is_active' => true,
            ],

            // From master_registry.yaml - COORD_X103
            [
                'coordinate_key' => 'COORD_X103',
                'label' => 'Error_Page',
                'description' => 'หน้าข้อผิดพลาด',
                'seo_keywords' => json_encode(['error', '404', 'not found']),
                'schema_type' => 'WebPage',
                'coordinate_address' => '1030.03030@',
                'bitmask_policy' => 0x0001,
                'version' => 1,
                'is_active' => true,
            ],

            // From master_registry.yaml - COORD_NAV_PROFILE
            [
                'coordinate_key' => 'COORD_NAV_PROFILE',
                'label' => 'Navigation_Profile',
                'description' => 'User profile navigation',
                'seo_keywords' => json_encode(['navigation', 'profile']),
                'schema_type' => 'NavigationElement',
                'coordinate_address' => '2000.00000@',
                'bitmask_policy' => 0x0010,
                'version' => 1,
                'is_active' => true,
            ],

            // From master_registry.yaml - COORD_NAV_DASHBOARD
            [
                'coordinate_key' => 'COORD_NAV_DASHBOARD',
                'label' => 'Navigation_Dashboard',
                'description' => 'Dashboard navigation',
                'seo_keywords' => json_encode(['navigation', 'dashboard']),
                'schema_type' => 'NavigationElement',
                'coordinate_address' => '2001.00000@',
                'bitmask_policy' => 0x0010,
                'version' => 1,
                'is_active' => true,
            ],
        ];

        DB::table('coordinate_mappings')->insertOrIgnore(
            array_map(
                fn (array $coord) => array_merge($coord, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]),
                $coordinates
            )
        );
    }
}
