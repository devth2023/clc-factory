<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\CoordinateResolutionException;
use App\Models\CoordinateMapping;
use App\ValueObjects\CoordinateData;

/**
 * Coordinate resolution service.
 *
 * Resolves coordinate keys through the three-layer registry system:
 * - Layer 1: Glossary (human metadata)
 * - Layer 2: Coordinate Address (unique identifier)
 * - Layer 3: Bitmask Policy (source of truth)
 *
 * All resolution is immutable and cached at the database level.
 */
final class CoordinateResolver
{
    private const COORDINATE_FORMAT = '/^[0-9a-f]{4}\.[0-9a-f]{4}@$/i';

    /**
     * Resolve a coordinate key to complete coordinate data.
     *
     * Fetches all three layers from the database and validates the format.
     *
     * @param string $coordinateKey The coordinate key to resolve (e.g., COORD_X101)
     * @return CoordinateData The resolved coordinate data
     * @throws CoordinateResolutionException
     */
    public function resolve(string $coordinateKey): CoordinateData
    {
        $mapping = $this->findMapping($coordinateKey);

        if (!$mapping) {
            throw CoordinateResolutionException::coordinateNotFound($coordinateKey);
        }

        $this->validateCoordinateAddress($mapping->coordinate_address);

        return CoordinateData::fromModel($mapping);
    }

    /**
     * Resolve multiple coordinates at once.
     *
     * @param string ...$coordinateKeys The coordinate keys to resolve
     * @return array<string, CoordinateData> Keyed by coordinate key
     * @throws CoordinateResolutionException If any coordinate is not found
     */
    public function resolveMany(string ...$coordinateKeys): array
    {
        $resolved = [];

        foreach ($coordinateKeys as $key) {
            $resolved[$key] = $this->resolve($key);
        }

        return $resolved;
    }

    /**
     * Try to resolve a coordinate, returning null if not found.
     *
     * @param string $coordinateKey The coordinate key to resolve
     * @return CoordinateData|null The resolved coordinate data or null
     */
    public function resolveOrNull(string $coordinateKey): ?CoordinateData
    {
        try {
            return $this->resolve($coordinateKey);
        } catch (CoordinateResolutionException) {
            return null;
        }
    }

    /**
     * Get only the bitmask policy for a coordinate (Layer 3).
     *
     * @param string $coordinateKey The coordinate key
     * @return int The bitmask policy value
     * @throws CoordinateResolutionException
     */
    public function resolveMask(string $coordinateKey): int
    {
        $mapping = $this->findMapping($coordinateKey);

        if (!$mapping) {
            throw CoordinateResolutionException::coordinateNotFound($coordinateKey);
        }

        return $mapping->bitmaskPolicy;
    }

    /**
     * Get only the glossary data for a coordinate (Layer 1).
     *
     * @param string $coordinateKey The coordinate key
     * @return array{
     *     label: string,
     *     description: string|null,
     *     seo_keywords: array<string>|null,
     *     schema_type: string|null
     * } The glossary data
     * @throws CoordinateResolutionException
     */
    public function resolveGlossary(string $coordinateKey): array
    {
        $mapping = $this->findMapping($coordinateKey);

        if (!$mapping) {
            throw CoordinateResolutionException::coordinateNotFound($coordinateKey);
        }

        return [
            'label' => $mapping->label ?? '',
            'description' => $mapping->description,
            'seo_keywords' => $mapping->seo_keywords,
            'schema_type' => $mapping->schema_type,
        ];
    }

    /**
     * Get only the coordinate address (Layer 2).
     *
     * @param string $coordinateKey The coordinate key
     * @return string The coordinate address
     * @throws CoordinateResolutionException
     */
    public function resolveAddress(string $coordinateKey): string
    {
        $mapping = $this->findMapping($coordinateKey);

        if (!$mapping) {
            throw CoordinateResolutionException::coordinateNotFound($coordinateKey);
        }

        return $mapping->coordinate_address;
    }

    /**
     * Check if a coordinate exists and is active.
     *
     * @param string $coordinateKey The coordinate key
     * @return bool True if coordinate exists and is active
     */
    public function exists(string $coordinateKey): bool
    {
        return $this->findMapping($coordinateKey) !== null;
    }

    /**
     * Validate coordinate address format.
     *
     * Format: XXXX.YYYY@ where X and Y are hexadecimal digits.
     *
     * @param string $address The coordinate address
     * @return void
     * @throws CoordinateResolutionException
     */
    public function validateCoordinateAddress(string $address): void
    {
        if (!preg_match(self::COORDINATE_FORMAT, $address)) {
            throw CoordinateResolutionException::invalidCoordinateFormat($address);
        }
    }

    /**
     * Find a mapping by coordinate key.
     *
     * Only returns active coordinates.
     *
     * @param string $coordinateKey The coordinate key
     * @return CoordinateMapping|null
     */
    private function findMapping(string $coordinateKey): ?CoordinateMapping
    {
        return CoordinateMapping::active()
            ->where('coordinate_key', $coordinateKey)
            ->first();
    }
}
