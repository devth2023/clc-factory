<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Bit registry seeder - Seeds the bit_registry table.
 *
 * Maps bit positions (0-63) to semantic names and categories.
 * Used for documentation and bit tracking only.
 */
final class BitRegistrySeeder extends Seeder
{
    /**
     * Seed the bit_registry table.
     *
     * @return void
     */
    public function run(): void
    {
        $bits = [
            // User State Flags (0-7)
            [0, 'IS_ACTIVE', 'user_state', 'User account is active'],
            [1, 'IS_VIP', 'user_state', 'User is VIP member'],
            [2, 'IS_VERIFIED', 'user_state', 'User email/phone verified'],
            [3, 'IS_BANNED', 'user_state', 'User is banned'],
            [4, 'IS_PREMIUM', 'user_state', 'User has premium subscription'],
            [5, 'IS_2FA_ENABLED', 'user_state', 'Two-factor authentication enabled'],
            [6, 'EMAIL_CONFIRMED', 'user_state', 'Email confirmed'],
            [7, 'PHONE_CONFIRMED', 'user_state', 'Phone confirmed'],

            // User Permissions (8-15)
            [8, 'CAN_READ', 'permission', 'Can read content'],
            [9, 'CAN_WRITE', 'permission', 'Can write/create content'],
            [10, 'CAN_DELETE', 'permission', 'Can delete content'],
            [11, 'CAN_ADMIN', 'permission', 'Has admin privileges'],
            [12, 'CAN_PUBLISH', 'permission', 'Can publish content'],
            [13, 'CAN_MODERATE', 'permission', 'Can moderate content'],
            [14, 'CAN_VERIFY_OTHERS', 'permission', 'Can verify other users'],
            [15, 'CAN_EXPORT', 'permission', 'Can export data'],

            // User Roles (16-23)
            [16, 'ROLE_USER', 'role', 'Regular user role'],
            [17, 'ROLE_MODERATOR', 'role', 'Moderator role'],
            [18, 'ROLE_ADMIN', 'role', 'Administrator role'],
            [19, 'ROLE_SYSTEM', 'role', 'System role'],
            [20, 'ROLE_BOT', 'role', 'Bot role'],
            [21, 'ROLE_GUEST', 'role', 'Guest role'],

            // User Attributes (24-31)
            [24, 'HAS_PROFILE_PICTURE', 'attribute', 'User has profile picture'],
            [25, 'HAS_BIO', 'attribute', 'User has bio/description'],
            [26, 'HAS_VERIFIED_EMAIL', 'attribute', 'User has verified email'],
            [27, 'HAS_STRIPE_PAYMENT', 'attribute', 'User has Stripe payment method'],
            [28, 'HAS_OAUTH_LOGIN', 'attribute', 'User has OAuth login configured'],

            // Document State (32-39)
            [32, 'IS_PUBLISHED', 'document_state', 'Document is published'],
            [33, 'IS_ARCHIVED', 'document_state', 'Document is archived'],
            [34, 'IS_DELETED', 'document_state', 'Document is soft-deleted'],
            [35, 'IS_LOCKED', 'document_state', 'Document is locked for editing'],
            [36, 'IS_REVIEWED', 'document_state', 'Document is reviewed'],
            [37, 'IS_FEATURED', 'document_state', 'Document is featured'],
            [38, 'IS_SPONSORED', 'document_state', 'Document is sponsored'],
            [39, 'NEEDS_APPROVAL', 'document_state', 'Document needs approval'],

            // Document Properties (40-47)
            [40, 'HAS_COMMENTS', 'document_property', 'Document has comments'],
            [41, 'HAS_ATTACHMENTS', 'document_property', 'Document has attachments'],
            [42, 'IS_PUBLIC', 'document_property', 'Document is public'],
            [43, 'IS_SHARED', 'document_property', 'Document is shared'],
            [44, 'REQUIRES_AUTH', 'document_property', 'Document requires authentication'],
            [45, 'HAS_EXPIRY', 'document_property', 'Document has expiry date'],
            [46, 'IS_SENSITIVE', 'document_property', 'Document is sensitive/NSFW'],
            [47, 'HAS_ENCRYPTION', 'document_property', 'Document is encrypted'],
        ];

        DB::table('bit_registry')->insertOrIgnore(
            array_map(
                fn (array $bit) => [
                    'bit_position' => $bit[0],
                    'bit_name' => $bit[1],
                    'bit_category' => $bit[2],
                    'description' => $bit[3],
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                $bits
            )
        );
    }
}
