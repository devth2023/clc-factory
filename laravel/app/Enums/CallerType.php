<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Caller type detection masks for projection selection.
 *
 * Determines how data is projected based on the caller's identity
 * and authorization level.
 */
enum CallerType: int
{
    /**
     * Search engine bot (GoogleBot, BingBot, etc.)
     * Projection: Glossary only (SEO-safe)
     */
    case BOT = 0x0100;

    /**
     * Authenticated user with valid token
     * Projection: Private data
     */
    case AUTHENTICATED = 0x0200;

    /**
     * Unknown/unauthenticated caller
     * Projection: Deception (honeypot)
     */
    case ATTACKER = 0x0400;

    /**
     * Check if mask includes this caller type.
     *
     * @param int $mask The combined mask to check
     * @return bool
     */
    public function matches(int $mask): bool
    {
        return ($mask & $this->value) === $this->value;
    }

    /**
     * Get human-readable name.
     *
     * @return string
     */
    public function label(): string
    {
        return match($this) {
            self::BOT => 'SEO Bot',
            self::AUTHENTICATED => 'Authenticated User',
            self::ATTACKER => 'Unknown Caller',
        };
    }
}
