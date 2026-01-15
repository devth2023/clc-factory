<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Projection seeder - Seeds the projections table.
 *
 * Creates three projections per coordinate:
 * - Glossary: For SEO bots (public metadata)
 * - Private: For authenticated users (coordinate data)
 * - Deception: For unknown callers (honeypot)
 */
final class ProjectionSeeder extends Seeder
{
    /**
     * Seed the projections table.
     *
     * @return void
     */
    public function run(): void
    {
        $projections = [
            // COORD_X101 Projections
            [
                'coordinate_key' => 'COORD_X101',
                'projection_type' => 'glossary',
                'payload' => json_encode([
                    'label' => 'User_Profile_Name',
                    'description' => 'ชื่อจริงสำหรับแสดงผล',
                    'keywords' => ['user', 'profile', 'name'],
                    'schema' => 'Person',
                ]),
            ],
            [
                'coordinate_key' => 'COORD_X101',
                'projection_type' => 'private',
                'payload' => json_encode([
                    'coordinate_key' => 'COORD_X101',
                    'address' => '1010.01010@',
                    'bitmask' => '1',
                    'version' => 1,
                ]),
            ],
            [
                'coordinate_key' => 'COORD_X101',
                'projection_type' => 'deception',
                'payload' => json_encode([
                    'error' => 'HoneyPot_Data_UserName',
                ]),
            ],

            // COORD_X102 Projections
            [
                'coordinate_key' => 'COORD_X102',
                'projection_type' => 'glossary',
                'payload' => json_encode([
                    'label' => 'Dashboard_Stats',
                    'description' => 'สถิติแดชบอร์ดสำหรับผู้ใช้',
                    'keywords' => ['dashboard', 'stats', 'analytics'],
                    'schema' => 'AggregateRating',
                ]),
            ],
            [
                'coordinate_key' => 'COORD_X102',
                'projection_type' => 'private',
                'payload' => json_encode([
                    'coordinate_key' => 'COORD_X102',
                    'address' => '1020.02020@',
                    'bitmask' => '2',
                    'version' => 1,
                ]),
            ],
            [
                'coordinate_key' => 'COORD_X102',
                'projection_type' => 'deception',
                'payload' => json_encode([
                    'error' => 'Scam_Stats_12345',
                ]),
            ],

            // COORD_X103 Projections
            [
                'coordinate_key' => 'COORD_X103',
                'projection_type' => 'glossary',
                'payload' => json_encode([
                    'label' => 'Error_Page',
                    'description' => 'หน้าข้อผิดพลาด',
                    'keywords' => ['error', '404', 'not found'],
                    'schema' => 'WebPage',
                ]),
            ],
            [
                'coordinate_key' => 'COORD_X103',
                'projection_type' => 'private',
                'payload' => json_encode([
                    'coordinate_key' => 'COORD_X103',
                    'address' => '1030.03030@',
                    'bitmask' => '1',
                    'version' => 1,
                ]),
            ],
            [
                'coordinate_key' => 'COORD_X103',
                'projection_type' => 'deception',
                'payload' => json_encode([
                    'error' => 'Fake_Error_Message',
                ]),
            ],

            // COORD_NAV_PROFILE Projections
            [
                'coordinate_key' => 'COORD_NAV_PROFILE',
                'projection_type' => 'glossary',
                'payload' => json_encode([
                    'label' => 'Navigation_Profile',
                    'description' => 'User profile navigation',
                    'keywords' => ['navigation', 'profile'],
                    'schema' => 'NavigationElement',
                ]),
            ],
            [
                'coordinate_key' => 'COORD_NAV_PROFILE',
                'projection_type' => 'private',
                'payload' => json_encode([
                    'coordinate_key' => 'COORD_NAV_PROFILE',
                    'address' => '2000.00000@',
                    'bitmask' => '10',
                    'version' => 1,
                ]),
            ],
            [
                'coordinate_key' => 'COORD_NAV_PROFILE',
                'projection_type' => 'deception',
                'payload' => json_encode([
                    'nav' => 'invalid_navigation',
                ]),
            ],

            // COORD_NAV_DASHBOARD Projections
            [
                'coordinate_key' => 'COORD_NAV_DASHBOARD',
                'projection_type' => 'glossary',
                'payload' => json_encode([
                    'label' => 'Navigation_Dashboard',
                    'description' => 'Dashboard navigation',
                    'keywords' => ['navigation', 'dashboard'],
                    'schema' => 'NavigationElement',
                ]),
            ],
            [
                'coordinate_key' => 'COORD_NAV_DASHBOARD',
                'projection_type' => 'private',
                'payload' => json_encode([
                    'coordinate_key' => 'COORD_NAV_DASHBOARD',
                    'address' => '2001.00000@',
                    'bitmask' => '10',
                    'version' => 1,
                ]),
            ],
            [
                'coordinate_key' => 'COORD_NAV_DASHBOARD',
                'projection_type' => 'deception',
                'payload' => json_encode([
                    'nav' => 'invalid_navigation',
                ]),
            ],
        ];

        DB::table('projections')->insertOrIgnore(
            array_map(
                fn (array $proj) => array_merge($proj, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]),
                $projections
            )
        );
    }
}
