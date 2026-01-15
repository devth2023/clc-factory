<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\BitMaskEngine;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Entity model - Base class for all bit-driven entities.
 *
 * Stores entity state in a single 64-bit field (status_flags).
 * Each bit position represents a specific state or permission.
 *
 * @property int $id
 * @property string $entity_type Type of entity (user, post, document, etc)
 * @property string $entity_id Unique identifier within entity_type
 * @property int $status_flags 64-bit state storage
 * @property string $coordinate_key Reference to coordinate_mappings
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read CoordinateMapping|null $coordinate
 */
final class Entity extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'entity_type',
        'entity_id',
        'status_flags',
        'coordinate_key',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status_flags' => 'integer',
    ];

    /**
     * The coordinate this entity references.
     *
     * @return BelongsTo<CoordinateMapping, static>
     */
    public function coordinate(): BelongsTo
    {
        return $this->belongsTo(CoordinateMapping::class, 'coordinate_key', 'coordinate_key');
    }

    /**
     * Get the bit mask engine instance.
     *
     * @return BitMaskEngine
     */
    private function engine(): BitMaskEngine
    {
        return app(BitMaskEngine::class);
    }

    /**
     * Check if a specific bit is set.
     *
     * @param int $position The bit position (0-63)
     * @return bool
     */
    public function hasBit(int $position): bool
    {
        return $this->engine()->hasBit($this->status_flags, $position);
    }

    /**
     * Set a specific bit.
     *
     * @param int $position The bit position (0-63)
     * @return $this
     */
    public function setBit(int $position): self
    {
        $this->status_flags = $this->engine()->setBit($this->status_flags, $position);
        return $this;
    }

    /**
     * Clear a specific bit.
     *
     * @param int $position The bit position (0-63)
     * @return $this
     */
    public function clearBit(int $position): self
    {
        $this->status_flags = $this->engine()->clearBit($this->status_flags, $position);
        return $this;
    }

    /**
     * Toggle a specific bit.
     *
     * @param int $position The bit position (0-63)
     * @return $this
     */
    public function toggleBit(int $position): self
    {
        $this->status_flags = $this->engine()->toggleBit($this->status_flags, $position);
        return $this;
    }

    /**
     * Check if all bits in a mask are set.
     *
     * @param int $mask The bitmask to check
     * @return bool
     */
    public function hasMask(int $mask): bool
    {
        return $this->engine()->hasMask($this->status_flags, $mask);
    }

    /**
     * Check if any bits in a mask are set.
     *
     * @param int $mask The bitmask to check
     * @return bool
     */
    public function hasAnyMask(int $mask): bool
    {
        return $this->engine()->hasAnyMask($this->status_flags, $mask);
    }

    /**
     * Apply a mask and return the result (non-mutating).
     *
     * @param int $mask The bitmask to apply
     * @return int
     */
    public function applyMask(int $mask): int
    {
        return $this->engine()->applyMask($this->status_flags, $mask);
    }

    /**
     * Set multiple bits from a mask.
     *
     * @param int $mask The mask bits to set
     * @return $this
     */
    public function setMask(int $mask): self
    {
        $this->status_flags = $this->engine()->setMask($this->status_flags, $mask);
        return $this;
    }

    /**
     * Clear multiple bits from a mask.
     *
     * @param int $mask The mask bits to clear
     * @return $this
     */
    public function clearMask(int $mask): self
    {
        $this->status_flags = $this->engine()->clearMask($this->status_flags, $mask);
        return $this;
    }

    /**
     * Get count of set bits.
     *
     * @return int
     */
    public function countSetBits(): int
    {
        return $this->engine()->countSetBits($this->status_flags);
    }

    /**
     * Get status flags as hexadecimal string.
     *
     * @return string
     */
    public function getFlagsHex(): string
    {
        return dechex($this->status_flags);
    }

    /**
     * Get status flags as binary string.
     *
     * @return string
     */
    public function getFlagsBinary(): string
    {
        return decbin($this->status_flags);
    }
}
