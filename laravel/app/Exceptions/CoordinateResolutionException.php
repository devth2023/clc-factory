<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * Thrown when coordinate resolution fails at any layer.
 *
 * This exception indicates failure to resolve a coordinate key
 * through the three-layer registry system.
 */
final class CoordinateResolutionException extends Exception
{
    /**
     * Create a new exception for missing coordinate.
     *
     * @param string $coordinateKey The coordinate key that was not found
     * @return self
     */
    public static function coordinateNotFound(string $coordinateKey): self
    {
        return new self(
            sprintf(
                'Coordinate not found: %s',
                $coordinateKey
            )
        );
    }

    /**
     * Create a new exception for invalid coordinate format.
     *
     * @param string $address The invalid coordinate address
     * @return self
     */
    public static function invalidCoordinateFormat(string $address): self
    {
        return new self(
            sprintf(
                'Invalid coordinate address format: %s. Expected format: XXXX.YYYY@',
                $address
            )
        );
    }

    /**
     * Create a new exception for layer resolution failure.
     *
     * @param int $layer The layer number (1, 2, or 3)
     * @param string $coordinateKey The coordinate key
     * @return self
     */
    public static function layerResolutionFailed(int $layer, string $coordinateKey): self
    {
        return new self(
            sprintf(
                'Layer %d resolution failed for coordinate: %s',
                $layer,
                $coordinateKey
            )
        );
    }
}
