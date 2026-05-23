<?php

declare(strict_types=1);

namespace App\Csv;

final class CsvReader
{
    public function read(string $filePath, string $delimiter = ','): CsvTable
    {
        if ($delimiter === '') {
            throw new \InvalidArgumentException('CSV delimiter cannot be empty.');
        }

        $file = new \SplFileObject($filePath);
        $file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY);
        $file->setCsvControl($delimiter, '"', '\\');

        $headers = null;
        $rows = [];

        foreach ($file as $lineNumber => $row) {
            if ($row === [null] || $row === false) {
                continue;
            }

            $row = array_map(
                static fn (?string $value): ?string => $value !== null ? trim($value) : null,
                $row
            );

            if ($headers === null) {
                $headers = $this->readHeaders($row);
                continue;
            }

            if (count($row) !== count($headers)) {
                throw new \RuntimeException(sprintf(
                    'Invalid CSV structure at line %d. Expected %d columns, got %d.',
                    $lineNumber + 1,
                    count($headers),
                    count($row)
                ));
            }

            $rows[] = array_combine($headers, $row);
        }

        if ($headers === null) {
            throw new \RuntimeException('CSV file is empty.');
        }

        if ($rows === []) {
            throw new \RuntimeException('CSV file does not contain data rows.');
        }

        return new CsvTable($headers, $rows);
    }

    /**
     * @param list<string|null> $row
     * @return list<string>
     */
    private function readHeaders(array $row): array
    {
        $headers = [];

        foreach ($row as $header) {
            $header = trim((string) $header);

            if ($header === '') {
                throw new \RuntimeException('CSV header contains an empty column name.');
            }

            if (in_array($header, $headers, true)) {
                throw new \RuntimeException(sprintf('Duplicate CSV header "%s".', $header));
            }

            $headers[] = $header;
        }

        return $headers;
    }
}
