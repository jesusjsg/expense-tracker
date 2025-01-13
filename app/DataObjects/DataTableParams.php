<?php

declare(strict_types = 1);

namespace App\DataObjects;

class DataTableParams
{
    public function __construct(
        public readonly int $start,
        public readonly int $length,
        public readonly string $orderBy,
        public readonly string $orderDir,
        public readonly string $searchValue,
        public readonly int $draw
    ) {
        
    }
}
