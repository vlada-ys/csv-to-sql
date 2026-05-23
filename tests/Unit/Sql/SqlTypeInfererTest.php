<?php

declare(strict_types=1);

namespace App\Tests\Unit\Sql;

use App\Sql\ColumnDefinition;
use App\Sql\SqlIdentifierNormalizer;
use App\Sql\SqlTypeInferer;
use PHPUnit\Framework\TestCase;

final class SqlTypeInfererTest extends TestCase
{
    private SqlTypeInferer $inferer;

    protected function setUp(): void
    {
        $this->inferer = new SqlTypeInferer(new SqlIdentifierNormalizer());
    }

    public function testItInfersColumnTypes(): void
    {
        $columns = $this->inferer->infer(
            ['Name', 'Age', 'Grade', 'Salary'],
            [
                ['Name' => 'Alice Smith', 'Age' => '29', 'Grade' => 'L3', 'Salary' => '55000.50'],
                ['Name' => 'Bob Johnson', 'Age' => '34', 'Grade' => 'L4', 'Salary' => '62000'],
                ['Name' => 'Charlie Lee', 'Age' => '41', 'Grade' => 'L5', 'Salary' => '72000.75'],
            ],
        );

        self::assertEquals([
            new ColumnDefinition('name', 'VARCHAR(11)', false),
            new ColumnDefinition('age', 'INTEGER', false),
            new ColumnDefinition('grade', 'VARCHAR(2)', false),
            new ColumnDefinition('salary', 'DECIMAL(15, 2)', false),
        ], $columns);
    }

    public function testItMarksColumnAsNullableWhenEmptyValueExists(): void
    {
        $columns = $this->inferer->infer(
            ['Name', 'Age'],
            [
                ['Name' => 'Alice Smith', 'Age' => '29'],
                ['Name' => '', 'Age' => '34'],
            ],
        );

        self::assertEquals(
            new ColumnDefinition('name', 'VARCHAR(11)', true),
            $columns[0],
        );

        self::assertEquals(
            new ColumnDefinition('age', 'INTEGER', false),
            $columns[1],
        );
    }

    public function testItInfersBooleanColumn(): void
    {
        $columns = $this->inferer->infer(
            ['Active'],
            [
                ['Active' => 'true'],
                ['Active' => 'false'],
                ['Active' => 'yes'],
            ],
        );

        self::assertEquals(
            new ColumnDefinition('active', 'BOOLEAN', false),
            $columns[0],
        );
    }

    public function testItInfersDateColumn(): void
    {
        $columns = $this->inferer->infer(
            ['Start Date'],
            [
                ['Start Date' => '2026-05-01'],
                ['Start Date' => '2026-05-20'],
            ],
        );

        self::assertEquals(
            new ColumnDefinition('start_date', 'DATE', false),
            $columns[0],
        );
    }

    public function testItUsesTextForLongValues(): void
    {
        $longValue = str_repeat('a', 256);

        $columns = $this->inferer->infer(
            ['Description'],
            [
                ['Description' => $longValue],
            ],
        );

        self::assertEquals(
            new ColumnDefinition('description', 'TEXT', false),
            $columns[0],
        );
    }
}
