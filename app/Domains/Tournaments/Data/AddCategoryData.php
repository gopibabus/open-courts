<?php

declare(strict_types=1);

namespace App\Domains\Tournaments\Data;

use App\Domains\Tournaments\Enums\CategoryType;

/**
 * Input for adding a category (event) to a tournament. `maxEntrants` null = unlimited.
 */
final readonly class AddCategoryData
{
    public function __construct(
        public string $name,
        public CategoryType $type,
        public ?int $maxEntrants,
    ) {}
}
