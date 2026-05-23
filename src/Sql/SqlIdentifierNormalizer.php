<?php

declare(strict_types=1);

namespace App\Sql;

final class SqlIdentifierNormalizer
{
    public function normalize(string $name): string
    {
        $normalized = strtolower(trim($name));
        $normalized = preg_replace('/[^a-z0-9_]+/', '_', $normalized);
        $normalized = trim((string) $normalized, '_');

        if ($normalized === '') {
            throw new \InvalidArgumentException('SQL identifier cannot be empty.');
        }

        if (preg_match('/^[0-9]/', $normalized) === 1) {
            $normalized = '_' . $normalized;
        }

        return $normalized;
    }
}
