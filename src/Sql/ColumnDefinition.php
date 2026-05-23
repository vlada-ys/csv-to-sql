<?php

declare(strict_types=1);

namespace App\Sql;

final readonly class ColumnDefinition
{
    public function __construct(
        public string $name,
        public string $type,
        public bool $nullable,
    ) {
    }
}
