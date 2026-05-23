<?php

declare(strict_types=1);

namespace App\Tests\Unit\Sql;

use App\Sql\SqlIdentifierNormalizer;
use PHPUnit\Framework\TestCase;

final class SqlIdentifierNormalizerTest extends TestCase
{
    public function testItNormalizesSqlIdentifier(): void
    {
        $normalizer = new SqlIdentifierNormalizer();

        self::assertSame('first_name', $normalizer->normalize('First Name'));
        self::assertSame('employee_id', $normalizer->normalize('Employee-ID'));
        self::assertSame('salary', $normalizer->normalize(' Salary '));
        self::assertSame('_123_code', $normalizer->normalize('123 Code'));
    }

    public function testItRejectsEmptyIdentifier(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('SQL identifier cannot be empty.');

        new SqlIdentifierNormalizer()->normalize('!!!');
    }
}
