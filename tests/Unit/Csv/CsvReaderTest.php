<?php

declare(strict_types=1);

namespace App\Tests\Unit\Csv;

use App\Csv\CsvReader;
use PHPUnit\Framework\TestCase;

final class CsvReaderTest extends TestCase
{
    public function testItReadsCsvFile(): void
    {
        $filePath = $this->createTemporaryCsv(
            <<<CSV
Name,Age,Grade
Alice Smith,29,L3
Bob Johnson,34,L4
CSV,
        );

        $table = new CsvReader()->read($filePath);

        self::assertSame(['Name', 'Age', 'Grade'], $table->headers);
        self::assertSame([
            ['Name' => 'Alice Smith', 'Age' => '29', 'Grade' => 'L3'],
            ['Name' => 'Bob Johnson', 'Age' => '34', 'Grade' => 'L4'],
        ], $table->rows);
    }

    public function testItRejectsDuplicateHeaders(): void
    {
        $filePath = $this->createTemporaryCsv(
            <<<CSV
Name,Age,Name
Alice Smith,29,L3
CSV,
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Duplicate CSV header "Name".');

        new CsvReader()->read($filePath);
    }

    public function testItRejectsInvalidColumnCount(): void
    {
        $filePath = $this->createTemporaryCsv(
            <<<CSV
Name,Age,Grade
Alice Smith,29
CSV,
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid CSV structure');

        new CsvReader()->read($filePath);
    }

    public function testItRejectsEmptyCsvFile(): void
    {
        $filePath = $this->createTemporaryCsv('');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('CSV file is empty.');

        new CsvReader()->read($filePath);
    }

    public function testItSupportsCustomDelimiter(): void
    {
        $filePath = $this->createTemporaryCsv(
            <<<CSV
Name;Age;Grade
Alice Smith;29;L3
Bob Johnson;34;L4
CSV,
        );

        $table = new CsvReader()->read($filePath, ';');

        self::assertSame(['Name', 'Age', 'Grade'], $table->headers);
        self::assertSame([
            ['Name' => 'Alice Smith', 'Age' => '29', 'Grade' => 'L3'],
            ['Name' => 'Bob Johnson', 'Age' => '34', 'Grade' => 'L4'],
        ], $table->rows);
    }

    private function createTemporaryCsv(string $content): string
    {
        $filePath = tempnam(sys_get_temp_dir(), 'csv_test_');

        if ($filePath === false) {
            self::fail('Failed to create temporary CSV file.');
        }

        file_put_contents($filePath, $content);

        return $filePath;
    }
}
