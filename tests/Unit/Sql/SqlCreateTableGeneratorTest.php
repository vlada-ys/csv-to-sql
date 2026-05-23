<?php

declare(strict_types=1);

namespace App\Tests\Unit\Sql;

use App\Sql\ColumnDefinition;
use App\Sql\SqlCreateTableGenerator;
use PHPUnit\Framework\TestCase;

final class SqlCreateTableGeneratorTest extends TestCase
{
    public function testItGeneratesCreateTableStatement(): void
    {
        $generator = new SqlCreateTableGenerator();

        $sql = $generator->generate('employees', [
            new ColumnDefinition('name', 'VARCHAR(12)', false),
            new ColumnDefinition('age', 'INTEGER', false),
            new ColumnDefinition('grade', 'VARCHAR(2)', false),
            new ColumnDefinition('salary', 'DECIMAL(15, 2)', false),
        ]);

        self::assertSame(
            <<<SQL
CREATE TABLE "employees" (
    "id" BIGSERIAL PRIMARY KEY,
    "name" VARCHAR(12) NOT NULL,
    "age" INTEGER NOT NULL,
    "grade" VARCHAR(2) NOT NULL,
    "salary" DECIMAL(15, 2) NOT NULL
);
SQL,
            $sql,
        );
    }

    public function testItGeneratesNullableColumn(): void
    {
        $generator = new SqlCreateTableGenerator();

        $sql = $generator->generate('employees', [
            new ColumnDefinition('name', 'VARCHAR(12)', true),
        ]);

        self::assertSame(
            <<<SQL
CREATE TABLE "employees" (
    "id" BIGSERIAL PRIMARY KEY,
    "name" VARCHAR(12) NULL
);
SQL,
            $sql,
        );
    }

    public function testItRejectsEmptyColumns(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot generate CREATE TABLE without columns.');

        new SqlCreateTableGenerator()->generate('employees', []);
    }
}
