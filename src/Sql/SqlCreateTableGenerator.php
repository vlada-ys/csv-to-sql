<?php

declare(strict_types=1);

namespace App\Sql;

final readonly class SqlCreateTableGenerator
{
    /**
     * @param list<ColumnDefinition> $columns
     */
    public function generate(string $tableName, array $columns): string
    {
        if ($columns === []) {
            throw new \InvalidArgumentException('Cannot generate CREATE TABLE without columns.');
        }

        $lines = [
            '    "id" BIGSERIAL PRIMARY KEY',
        ];

        foreach ($columns as $column) {
            $lines[] = sprintf(
                '    "%s" %s %s',
                $column->name,
                $column->type,
                $column->nullable ? 'NULL' : 'NOT NULL',
            );
        }

        return sprintf(
            "CREATE TABLE \"%s\" (\n%s\n);",
            $tableName,
            implode(",\n", $lines),
        );
    }
}
