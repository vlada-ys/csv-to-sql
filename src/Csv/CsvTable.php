<?php

declare(strict_types=1);

namespace App\Csv;

final readonly class CsvTable
{
    /**
     * @param list<string> $headers
     * @param list<array<string, string|null>> $rows
     */
    public function __construct(
        public array $headers,
        public array $rows,
    ) {
    }
}
