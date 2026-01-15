<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * Thrown when a bitmask operation receives an invalid mask value.
 *
 * This exception indicates a programming error or invalid external input
 * attempting to use invalid bit positions or mask values.
 */
final class InvalidBitmaskException extends Exception
{
    /**
     * Create a new exception for invalid bit position.
     *
     * @param int $position The invalid bit position (0-63)
     * @return self
     */
    public static function invalidBitPosition(int $position): self
    {
        return new self(
            sprintf(
                'Invalid bit position: %d. Must be between 0 and 63.',
                $position
            )
        );
    }

    /**
     * Create a new exception for invalid mask value.
     *
     * @param int $mask The invalid mask value
     * @return self
     */
    public static function invalidMaskValue(int $mask): self
    {
        return new self(
            sprintf(
                'Invalid mask value: %d. Mask must be non-negative.',
                $mask
            )
        );
    }

    /**
     * Create a new exception for negative flag value.
     *
     * @param int $flags The negative flag value
     * @return self
     */
    public static function negativeFlagValue(int $flags): self
    {
        return new self(
            sprintf(
                'Invalid flag value: %d. Flags must be non-negative.',
                $flags
            )
        );
    }
}
