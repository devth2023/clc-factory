<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BitPosition;
use App\Exceptions\InvalidBitmaskException;

/**
 * Core bitmask operations engine.
 *
 * Provides atomic bitwise operations for flag manipulation.
 * All operations are constant-time O(1) CPU register operations.
 *
 * @psalm-immutable
 */
final class BitMaskEngine
{
    private const MIN_BIT_POSITION = 0;
    private const MAX_BIT_POSITION = 63;

    /**
     * Set a specific bit to 1.
     *
     * @param int $flags The flag value to modify
     * @param int $position The bit position (0-63)
     * @return int The modified flags
     * @throws InvalidBitmaskException
     */
    public function setBit(int $flags, int $position): int
    {
        $this->validateBitPosition($position);

        return $flags | (1 << $position);
    }

    /**
     * Clear a specific bit to 0.
     *
     * @param int $flags The flag value to modify
     * @param int $position The bit position (0-63)
     * @return int The modified flags
     * @throws InvalidBitmaskException
     */
    public function clearBit(int $flags, int $position): int
    {
        $this->validateBitPosition($position);

        return $flags & ~(1 << $position);
    }

    /**
     * Toggle a specific bit.
     *
     * @param int $flags The flag value to modify
     * @param int $position The bit position (0-63)
     * @return int The modified flags
     * @throws InvalidBitmaskException
     */
    public function toggleBit(int $flags, int $position): int
    {
        $this->validateBitPosition($position);

        return $flags ^ (1 << $position);
    }

    /**
     * Check if a specific bit is set (1).
     *
     * @param int $flags The flag value to check
     * @param int $position The bit position (0-63)
     * @return bool True if bit is set
     * @throws InvalidBitmaskException
     */
    public function hasBit(int $flags, int $position): bool
    {
        $this->validateBitPosition($position);

        return (bool)(($flags >> $position) & 1);
    }

    /**
     * Apply a mask to flags (bitwise AND).
     *
     * Returns bits that are set in both flags and mask.
     *
     * @param int $flags The flag value
     * @param int $mask The mask to apply
     * @return int The result of bitwise AND
     * @throws InvalidBitmaskException
     */
    public function applyMask(int $flags, int $mask): int
    {
        $this->validateFlags($flags);
        $this->validateFlags($mask);

        return $flags & $mask;
    }

    /**
     * Check if all bits in mask are set in flags.
     *
     * @param int $flags The flag value to check
     * @param int $mask The mask to check
     * @return bool True if all mask bits are set in flags
     * @throws InvalidBitmaskException
     */
    public function hasMask(int $flags, int $mask): bool
    {
        $this->validateFlags($flags);
        $this->validateFlags($mask);

        return ($flags & $mask) === $mask;
    }

    /**
     * Check if any bit in mask is set in flags.
     *
     * @param int $flags The flag value to check
     * @param int $mask The mask to check
     * @return bool True if any mask bit is set in flags
     * @throws InvalidBitmaskException
     */
    public function hasAnyMask(int $flags, int $mask): bool
    {
        $this->validateFlags($flags);
        $this->validateFlags($mask);

        return ($flags & $mask) !== 0;
    }

    /**
     * Set multiple bits at once using a mask.
     *
     * @param int $flags The flag value to modify
     * @param int $mask The mask bits to set
     * @return int The modified flags
     * @throws InvalidBitmaskException
     */
    public function setMask(int $flags, int $mask): int
    {
        $this->validateFlags($flags);
        $this->validateFlags($mask);

        return $flags | $mask;
    }

    /**
     * Clear multiple bits at once using a mask.
     *
     * @param int $flags The flag value to modify
     * @param int $mask The mask bits to clear
     * @return int The modified flags
     * @throws InvalidBitmaskException
     */
    public function clearMask(int $flags, int $mask): int
    {
        $this->validateFlags($flags);
        $this->validateFlags($mask);

        return $flags & ~$mask;
    }

    /**
     * Create a mask from multiple bit positions.
     *
     * @param BitPosition ...$positions Variable number of bit positions
     * @return int The combined mask
     */
    public function buildMask(BitPosition ...$positions): int
    {
        $mask = 0;
        foreach ($positions as $position) {
            $mask |= $position->mask();
        }

        return $mask;
    }

    /**
     * Get count of set bits (population count).
     *
     * @param int $flags The flag value
     * @return int Number of set bits
     * @throws InvalidBitmaskException
     */
    public function countSetBits(int $flags): int
    {
        $this->validateFlags($flags);

        // Use PHP's built-in population count
        return substr_count(decbin($flags), '1');
    }

    /**
     * Validate bit position is within valid range.
     *
     * @param int $position The bit position to validate
     * @return void
     * @throws InvalidBitmaskException
     */
    private function validateBitPosition(int $position): void
    {
        if ($position < self::MIN_BIT_POSITION || $position > self::MAX_BIT_POSITION) {
            throw InvalidBitmaskException::invalidBitPosition($position);
        }
    }

    /**
     * Validate flag value is non-negative.
     *
     * @param int $flags The flag value to validate
     * @return void
     * @throws InvalidBitmaskException
     */
    private function validateFlags(int $flags): void
    {
        if ($flags < 0) {
            throw InvalidBitmaskException::negativeFlagValue($flags);
        }
    }
}
