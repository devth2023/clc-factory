<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProjectionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Projection model - Shadow data for different caller types.
 *
 * Stores three projection types per coordinate:
 * - Glossary: For SEO bots (public metadata)
 * - Private: For authenticated users (full data)
 * - Deception: For unknown callers (honeypot)
 *
 * @property int $id
 * @property string $coordinate_key Reference to coordinate_mappings
 * @property ProjectionType $projection_type Type of projection
 * @property array<mixed> $payload The shadow data
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read CoordinateMapping $coordinate
 */
final class Projection extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'coordinate_key',
        'projection_type',
        'payload',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'projection_type' => ProjectionType::class,
        'payload' => 'array',
    ];

    /**
     * The coordinate this projection belongs to.
     *
     * @return BelongsTo<CoordinateMapping, static>
     */
    public function coordinate(): BelongsTo
    {
        return $this->belongsTo(CoordinateMapping::class, 'coordinate_key', 'coordinate_key');
    }

    /**
     * Get projection payload as array.
     *
     * @return array<mixed>
     */
    public function getPayload(): array
    {
        return $this->payload ?? [];
    }

    /**
     * Check if this is a glossary projection.
     *
     * @return bool
     */
    public function isGlossary(): bool
    {
        return $this->projection_type === ProjectionType::GLOSSARY;
    }

    /**
     * Check if this is a private projection.
     *
     * @return bool
     */
    public function isPrivate(): bool
    {
        return $this->projection_type === ProjectionType::PRIVATE;
    }

    /**
     * Check if this is a deception projection.
     *
     * @return bool
     */
    public function isDeception(): bool
    {
        return $this->projection_type === ProjectionType::DECEPTION;
    }
}
