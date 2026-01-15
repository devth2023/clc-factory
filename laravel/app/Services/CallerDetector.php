<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CallerType;
use Illuminate\Http\Request;

/**
 * Caller detection service.
 *
 * Identifies the type of caller (SEO bot, authenticated user, or attacker)
 * based on User-Agent header and authorization token.
 *
 * Detection result determines which projection is rendered.
 */
final class CallerDetector
{
    /**
     * Known SEO bot user agent patterns.
     *
     * @var array<string>
     */
    private const SEO_BOT_PATTERNS = [
        'googlebot',
        'bingbot',
        'slurp',      // Yahoo
        'duckduckbot',
        'baiduspider',
        'yandexbot',
        'facebookexternalhit',
        'twitterbot',
        'linkedinbot',
        'whatsapp',
        'slackchannel',
        'pinterestbot',
    ];

    /**
     * Detect the caller type from the request.
     *
     * Detection priority:
     * 1. SEO bot (User-Agent match)
     * 2. Authenticated user (Authorization header)
     * 3. Default: Attacker/Unknown
     *
     * @param Request $request The HTTP request
     * @return int The caller type bitmask
     */
    public function detect(Request $request): int
    {
        $userAgent = $request->header('User-Agent', '');
        $authToken = $request->header('Authorization', '');

        // Check for SEO bot first (highest priority)
        if ($this->isSeoBot($userAgent)) {
            return CallerType::BOT->value;
        }

        // Check for authenticated user
        if ($this->isAuthenticated($authToken)) {
            return CallerType::AUTHENTICATED->value;
        }

        // Default: treat as attacker/unknown caller
        return CallerType::ATTACKER->value;
    }

    /**
     * Check if user agent is a known SEO bot.
     *
     * @param string $userAgent The User-Agent header value
     * @return bool True if the user agent matches a known bot pattern
     */
    private function isSeoBot(string $userAgent): bool
    {
        $userAgentLower = strtolower($userAgent);

        foreach (self::SEO_BOT_PATTERNS as $pattern) {
            if (stripos($userAgentLower, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if request has valid authentication.
     *
     * @param string $authToken The Authorization header value
     * @return bool True if authentication token is present and valid
     */
    private function isAuthenticated(string $authToken): bool
    {
        if (empty($authToken)) {
            return false;
        }

        // Basic format check: "Bearer <token>" or "Token <token>"
        if (!preg_match('/^(Bearer|Token)\s+\S+$/i', $authToken)) {
            return false;
        }

        // Additional validation could be added here
        // (e.g., token blacklist check, expiry validation)

        return true;
    }

    /**
     * Get human-readable label for caller type.
     *
     * @param int $callerMask The caller type bitmask
     * @return string Human-readable label
     */
    public function getLabel(int $callerMask): string
    {
        return match ($callerMask) {
            CallerType::BOT->value => 'SEO Bot',
            CallerType::AUTHENTICATED->value => 'Authenticated User',
            CallerType::ATTACKER->value => 'Unknown Caller',
            default => 'Unknown Type',
        };
    }
}
