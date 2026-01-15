<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Models\CoordinateMapping;

/**
 * Immutable value object for resolved coordinate data.
 *
 * Represents the complete resolution of a coordinate through all three layers.
 *
 * @psalm-immutable
 */
final class CoordinateData
{
    /**
     * Create a new coordinate data instance.
     *
     * @param string $coordinateKey The coordinate key (e.g., COORD_X101)
     * @param string $label Human-readable label (Layer 1)
     * @param string|null $description Human-readable description (Layer 1)
     * @param array<string>|null $seoKeywords SEO keywords (Layer 1)
     * @param string|null $schemaType Schema.org type (Layer 1)
     * @param string $coordinateAddress Unique address (Layer 2, format: XXXX.YYYY@)
     * @param int $bitmaskPolicy Bitmask policy (Layer 3 - The Truth)
     * @param int $version Registry version
     * @param bool $isActive Whether coordinate is active
     */
    public function __construct(
        public readonly string $coordinateKey,
        public readonly string $label,
        public readonly ?string $description,
        public readonly ?array $seoKeywords,
        public readonly ?string $schemaType,
        public readonly string $coordinateAddress,
        public readonly int $bitmaskPolicy,
        public readonly int $version,
        public readonly bool $isActive,
    ) {
    }

    /**
     * Create from a CoordinateMapping model.
     *
     * @param CoordinateMapping $mapping The model to convert
     * @return self
     */
    public static function fromModel(CoordinateMapping $mapping): self
    {
        return new self(
            coordinateKey: $mapping->coordinate_key,
            label: $mapping->label ?? '',
            description: $mapping->description,
            seoKeywords: $mapping->seo_keywords,
            schemaType: $mapping->schema_type,
            coordinateAddress: $mapping->coordinate_address,
            bitmaskPolicy: $mapping->bitmaskPolicy,
            version: $mapping->version,
            isActive: $mapping->is_active,
        );
    }

    /**
     * Convert to array representation.
     *
     * @return array{
     *     coordinate_key: string,
     *     label: string,
     *     description: string|null,
     *     seo_keywords: array<string>|null,
     *     schema_type: string|null,
     *     coordinate_address: string,
     *     bitmask_policy: int,
     *     version: int,
     *     is_active: bool
     * }
     */
    public function toArray(): array
    {
        return [
            'coordinate_key' => $this->coordinateKey,
            'label' => $this->label,
            'description' => $this->description,
            'seo_keywords' => $this->seoKeywords,
            'schema_type' => $this->schemaType,
            'coordinate_address' => $this->coordinateAddress,
            'bitmask_policy' => $this->bitmaskPolicy,
            'version' => $this->version,
            'is_active' => $this->isActive,
        ];
    }

    /**
     * Get the bitmask policy as hexadecimal.
     *
     * @return string
     */
    public function getBitmaskHex(): string
    {
        return dechex($this->bitmaskPolicy);
    }
}
