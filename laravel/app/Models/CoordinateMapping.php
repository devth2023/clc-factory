<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Coordinate mapping model.
 *
 * Represents the three-layer registry system:
 * - Layer 1: Glossary (human labels, SEO metadata)
 * - Layer 2: Coordinate address (unique identifier)
 * - Layer 3: Bitmask policy (logic rules)
 *
 * @property int $id
 * @property string $coordinate_key Layer 1+2 reference (unique)
 * @property string $label Human-readable label
 * @property string $description Description for SEO/humans
 * @property array<string> $seo_keywords SEO keywords for indexing
 * @property string $schema_type Schema.org type
 * @property string $coordinate_address Unique coordinate (format: XXXX.YYYY@)
 * @property int $bitmask_policy The source of truth (Layer 3)
 * @property int $version Registry version
 * @property bool $is_active Active status
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\Projection> $projections
 */
final class CoordinateMapping extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'coordinate_key',
        'label',
        'description',
        'seo_keywords',
        'schema_type',
        'coordinate_address',
        'bitmask_policy',
        'version',
        'is_active',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'seo_keywords' => 'array',
        'bitmask_policy' => 'integer',
        'version' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Projections for this coordinate.
     *
     * @return HasMany<Projection>
     */
    public function projections(): HasMany
    {
        return $this->hasMany(Projection::class, 'coordinate_key', 'coordinate_key');
    }

    /**
     * Scope to active coordinates only.
     *
     * @param \Illuminate\Database\Eloquent\Builder<static> $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the bitmask policy as int.
     *
     * @return int
     */
    public function getBitmaskPolicy(): int
    {
        return (int)$this->bitmask_policy;
    }

    /**
     * Check if this coordinate has all bits in mask set.
     *
     * @param int $mask The bitmask to check
     * @return bool
     */
    public function hasMaskBits(int $mask): bool
    {
        return ($this->getBitmaskPolicy() & $mask) === $mask;
    }

    /**
     * Check if this coordinate has any bits in mask set.
     *
     * @param int $mask The bitmask to check
     * @return bool
     */
    public function hasAnyMaskBits(int $mask): bool
    {
        return ($this->getBitmaskPolicy() & $mask) !== 0;
    }
}
