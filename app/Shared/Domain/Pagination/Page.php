<?php

namespace App\Shared\Domain\Pagination;

/**
 * Framework-agnostic paginated result.
 *
 * @template T
 */
final readonly class Page
{
    /**
     * @param  array<int, T>  $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $perPage,
        public int $currentPage,
    ) {}

    public function lastPage(): int
    {
        return max(1, (int) ceil($this->total / $this->perPage));
    }

    public function hasPages(): bool
    {
        return $this->lastPage() > 1;
    }

    public function from(): int
    {
        return $this->total === 0 ? 0 : (($this->currentPage - 1) * $this->perPage) + 1;
    }

    public function to(): int
    {
        return min($this->currentPage * $this->perPage, $this->total);
    }
}
