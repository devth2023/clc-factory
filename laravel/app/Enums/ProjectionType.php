<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Projection types for shadow data rendering.
 *
 * Defines how data is presented based on caller type and permissions.
 */
enum ProjectionType: string
{
    /**
     * Glossary projection for SEO bots.
     * Contains public metadata, schema.org, keywords.
     */
    case GLOSSARY = 'glossary';

    /**
     * Private projection for authenticated users.
     * Contains full data with coordinate information.
     */
    case PRIVATE = 'private';

    /**
     * Deception projection for unknown callers.
     * Contains honeypot data to trap attackers/scrapers.
     */
    case DECEPTION = 'deception';

    /**
     * Get human-readable label.
     *
     * @return string
     */
    public function label(): string
    {
        return match($this) {
            self::GLOSSARY => 'Public Glossary',
            self::PRIVATE => 'Private Data',
            self::DECEPTION => 'Honeypot',
        };
    }
}
