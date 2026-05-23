<?php

declare(strict_types=1);

namespace App\Sql;

final readonly class SqlTypeInferer
{
    public function __construct(
        private SqlIdentifierNormalizer $identifierNormalizer,
    ) {
    }

    /**
     * @param list<string> $headers
     * @param list<array<string, string|null>> $rows
     * @return list<ColumnDefinition>
     */
    public function infer(array $headers, array $rows): array
    {
        $columns = [];

        foreach ($headers as $header) {
            $values = array_map(
                static fn (array $row): ?string => $row[$header] ?? null,
                $rows
            );

            $columns[] = new ColumnDefinition(
                name: $this->identifierNormalizer->normalize($header),
                type: $this->inferType($values),
                nullable: $this->hasEmptyValue($values)
            );
        }

        return $columns;
    }

    /**
     * @param list<string|null> $values
     */
    private function inferType(array $values): string
    {
        $nonEmptyValues = array_values(array_filter(
            $values,
            static fn (?string $value): bool => $value !== null && $value !== ''
        ));

        if ($nonEmptyValues === []) {
            return 'TEXT';
        }

        if ($this->allMatch($nonEmptyValues, static fn (string $value): bool => preg_match('/^-?\d+$/', $value) === 1)) {
            return 'INTEGER';
        }

        if ($this->allMatch($nonEmptyValues, static fn (string $value): bool => is_numeric($value))) {
            return 'DECIMAL(15, 2)';
        }

        if ($this->allMatch($nonEmptyValues, static fn (string $value): bool => self::isBoolean($value))) {
            return 'BOOLEAN';
        }

        if ($this->allMatch($nonEmptyValues, static fn (string $value): bool => self::isDate($value))) {
            return 'DATE';
        }

        $maxLength = max(array_map('strlen', $nonEmptyValues));

        if ($maxLength <= 255) {
            return sprintf('VARCHAR(%d)', max($maxLength, 1));
        }

        return 'TEXT';
    }

    /**
     * @param list<string|null> $values
     */
    private function hasEmptyValue(array $values): bool
    {
        foreach ($values as $value) {
            if ($value === null || $value === '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<string> $values
     */
    private function allMatch(array $values, callable $predicate): bool
    {
        foreach ($values as $value) {
            if (!$predicate($value)) {
                return false;
            }
        }

        return true;
    }

    private static function isBoolean(string $value): bool
    {
        return in_array(strtolower($value), ['true', 'false', 'yes', 'no', '0', '1'], true);
    }

    private static function isDate(string $value): bool
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }
}
