<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Bit position registry for entity state flags.
 *
 * Each enum case represents a specific bit position (0-63)
 * within the 64-bit status_flags field.
 *
 * @see https://bit-driven-architecture.local/docs/bitmask-guide
 */
enum BitPosition: int
{
    // User State Flags (0-7)
    case IS_ACTIVE = 0;
    case IS_VIP = 1;
    case IS_VERIFIED = 2;
    case IS_BANNED = 3;
    case IS_PREMIUM = 4;
    case IS_2FA_ENABLED = 5;
    case EMAIL_CONFIRMED = 6;
    case PHONE_CONFIRMED = 7;

    // User Permissions (8-15)
    case CAN_READ = 8;
    case CAN_WRITE = 9;
    case CAN_DELETE = 10;
    case CAN_ADMIN = 11;
    case CAN_PUBLISH = 12;
    case CAN_MODERATE = 13;
    case CAN_VERIFY_OTHERS = 14;
    case CAN_EXPORT = 15;

    // User Roles (16-23)
    case ROLE_USER = 16;
    case ROLE_MODERATOR = 17;
    case ROLE_ADMIN = 18;
    case ROLE_SYSTEM = 19;
    case ROLE_BOT = 20;
    case ROLE_GUEST = 21;

    // User Attributes (24-31)
    case HAS_PROFILE_PICTURE = 24;
    case HAS_BIO = 25;
    case HAS_VERIFIED_EMAIL = 26;
    case HAS_STRIPE_PAYMENT = 27;
    case HAS_OAUTH_LOGIN = 28;

    // Document State (32-39)
    case IS_PUBLISHED = 32;
    case IS_ARCHIVED = 33;
    case IS_DELETED = 34;
    case IS_LOCKED = 35;
    case IS_REVIEWED = 36;
    case IS_FEATURED = 37;
    case IS_SPONSORED = 38;
    case NEEDS_APPROVAL = 39;

    // Document Properties (40-47)
    case HAS_COMMENTS = 40;
    case HAS_ATTACHMENTS = 41;
    case IS_PUBLIC = 42;
    case IS_SHARED = 43;
    case REQUIRES_AUTH = 44;
    case HAS_EXPIRY = 45;
    case IS_SENSITIVE = 46;
    case HAS_ENCRYPTION = 47;

    // Custom/Extension Bits (48-63)
    case CUSTOM_FLAG_48 = 48;
    case CUSTOM_FLAG_49 = 49;
    case CUSTOM_FLAG_50 = 50;
    case CUSTOM_FLAG_51 = 51;
    case CUSTOM_FLAG_52 = 52;
    case CUSTOM_FLAG_53 = 53;
    case CUSTOM_FLAG_54 = 54;
    case CUSTOM_FLAG_55 = 55;
    case CUSTOM_FLAG_56 = 56;
    case CUSTOM_FLAG_57 = 57;
    case CUSTOM_FLAG_58 = 58;
    case CUSTOM_FLAG_59 = 59;
    case CUSTOM_FLAG_60 = 60;
    case CUSTOM_FLAG_61 = 61;
    case CUSTOM_FLAG_62 = 62;
    case CUSTOM_FLAG_63 = 63;

    /**
     * Get the bit mask value for this position.
     *
     * @return int The bitmask (1 << position)
     */
    public function mask(): int
    {
        return 1 << $this->value;
    }

    /**
     * Validate if bit position is within valid range.
     *
     * @param int $position The bit position to validate
     * @return bool True if position is 0-63
     */
    public static function isValid(int $position): bool
    {
        return $position >= 0 && $position <= 63;
    }
}
