<?php

declare(strict_types=1);

namespace App\Seo;

/**
 * Métadonnées d'une page rendue côté serveur : balises meta, URL canonique et données structurées.
 */
final readonly class SeoPage
{
    /**
     * @param list<array<string, mixed>> $jsonLd blocs schema.org, chacun rendu dans un <script type="application/ld+json">
     */
    public function __construct(
        public string $title,
        public string $description,
        public string $canonicalUrl,
        public array $jsonLd = [],
        public string $robots = 'index, follow',
        public string $ogType = 'website',
    ) {}
}
