<?php

declare(strict_types=1);

namespace Harbor\Pagination;

require_once __DIR__.'/../Support/value.php';

use function Harbor\Support\harbor_is_blank;

/**
 * Class PaginationOptionsBag.
 */
final class PaginationOptionsBag
{
    /**
     * @var array<string, mixed>
     */
    private array $query = [];

    private ?string $base_path = null;
    private int $max_per_page = 100;

    /**
     * @param array<string, mixed> $query
     */
    public function __construct(
        ?string $base_path = null,
        array $query = [],
        int $max_per_page = 100
    ) {
        $this->set_base_path($base_path);
        $this->set_query($query);
        $this->set_max_per_page($max_per_page);
    }

    /**
     * @param array<string, mixed> $query
     */
    public static function make(
        ?string $base_path = null,
        array $query = [],
        int $max_per_page = 100
    ): self {
        return new self($base_path, $query, $max_per_page);
    }

    public function set_base_path(?string $base_path): static
    {
        if (! is_string($base_path)) {
            $this->base_path = null;

            return $this;
        }

        $normalized_base_path = trim($base_path);
        if (harbor_is_blank($normalized_base_path)) {
            $this->base_path = null;

            return $this;
        }

        $this->base_path = $normalized_base_path;

        return $this;
    }

    /**
     * @param array<string, mixed> $query
     */
    public function set_query(array $query): static
    {
        $this->query = $query;

        return $this;
    }

    public function set_max_per_page(int $max_per_page): static
    {
        if ($max_per_page < 1) {
            throw new \InvalidArgumentException('Pagination max_per_page must be >= 1.');
        }

        $this->max_per_page = $max_per_page;

        return $this;
    }

    public function base_path(): ?string
    {
        return $this->base_path;
    }

    /**
     * @return array<string, mixed>
     */
    public function query(): array
    {
        return $this->query;
    }

    public function max_per_page(): int
    {
        return $this->max_per_page;
    }
}
