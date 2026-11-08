<?php

declare(strict_types=1);

namespace App\DTO\Response;

/**
 * @template T of object
 */
final readonly class PaginatedResponseDto
{
    /**
     * @param list<T> $items
     */
    public function __construct(
        public array $items,
        public int $page,
        public int $limit,
        public int $total,
        public int $pages,
    ) {}
}
