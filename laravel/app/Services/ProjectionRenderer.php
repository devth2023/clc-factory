<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CallerType;
use App\Enums\ProjectionType;
use App\Models\Projection;
use App\ValueObjects\CoordinateData;

/**
 * Projection renderer service.
 *
 * Renders shadow data based on caller type and coordinate data.
 * Implements the "Projection over Transmission" philosophy:
 * - Glossary: For SEO bots (public metadata only)
 * - Private: For authenticated users (full coordinate data)
 * - Deception: For unknown callers (honeypot payload)
 */
final class ProjectionRenderer
{
    /**
     * Render projection based on caller type.
     *
     * @param string $coordinateKey The coordinate key
     * @param CoordinateData $coordinateData The resolved coordinate data
     * @param int $callerMask The caller type bitmask
     * @return array{
     *     type: string,
     *     data: mixed,
     *     mask: string
     * } The projected response
     */
    public function render(
        string $coordinateKey,
        CoordinateData $coordinateData,
        int $callerMask
    ): array {
        $projectionType = $this->selectProjectionType($callerMask);

        $payload = $this->getProjectionPayload($coordinateKey, $projectionType);

        return [
            'type' => $projectionType->value,
            'data' => $payload,
            'mask' => dechex($callerMask),
        ];
    }

    /**
     * Render glossary projection (for SEO bots).
     *
     * @param CoordinateData $coordinateData The coordinate data
     * @return array{
     *     label: string,
     *     description: string|null,
     *     keywords: array<string>|null,
     *     schema: string|null
     * } The glossary shadow
     */
    public function renderGlossary(CoordinateData $coordinateData): array
    {
        return [
            'label' => $coordinateData->label,
            'description' => $coordinateData->description,
            'keywords' => $coordinateData->seoKeywords,
            'schema' => $coordinateData->schemaType,
        ];
    }

    /**
     * Render private projection (for authenticated users).
     *
     * @param CoordinateData $coordinateData The coordinate data
     * @return array{
     *     coordinate_key: string,
     *     address: string,
     *     bitmask: string,
     *     version: int
     * } The private shadow
     */
    public function renderPrivate(CoordinateData $coordinateData): array
    {
        return [
            'coordinate_key' => $coordinateData->coordinateKey,
            'address' => $coordinateData->coordinateAddress,
            'bitmask' => $coordinateData->getBitmaskHex(),
            'version' => $coordinateData->version,
        ];
    }

    /**
     * Render deception projection (for unknown callers).
     *
     * Returns honeypot data from database to trap attackers/scrapers.
     *
     * @param string $coordinateKey The coordinate key
     * @return mixed The deception payload
     */
    public function renderDeception(string $coordinateKey): mixed
    {
        $projection = Projection::where('coordinate_key', $coordinateKey)
            ->where('projection_type', ProjectionType::DECEPTION->value)
            ->first();

        return $projection?->getPayload() ?? ['error' => 'INVALID_COORDINATE'];
    }

    /**
     * Select projection type based on caller mask.
     *
     * @param int $callerMask The caller type bitmask
     * @return ProjectionType The selected projection type
     */
    private function selectProjectionType(int $callerMask): ProjectionType
    {
        if (($callerMask & CallerType::BOT->value) === CallerType::BOT->value) {
            return ProjectionType::GLOSSARY;
        }

        if (($callerMask & CallerType::AUTHENTICATED->value) === CallerType::AUTHENTICATED->value) {
            return ProjectionType::PRIVATE;
        }

        return ProjectionType::DECEPTION;
    }

    /**
     * Get projection payload from database or render inline.
     *
     * @param string $coordinateKey The coordinate key
     * @param ProjectionType $projectionType The projection type
     * @return mixed The payload to return
     */
    private function getProjectionPayload(
        string $coordinateKey,
        ProjectionType $projectionType
    ): mixed {
        $projection = Projection::where('coordinate_key', $coordinateKey)
            ->where('projection_type', $projectionType->value)
            ->first();

        return $projection?->getPayload() ?? ['error' => 'PROJECTION_NOT_FOUND'];
    }
}
