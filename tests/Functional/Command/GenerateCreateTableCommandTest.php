<?php

declare(strict_types=1);

namespace App\Tests\Functional\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class GenerateCreateTableCommandTest extends KernelTestCase
{
    public function testItGeneratesCreateTableStatementFromCsvFile(): void
    {
        self::bootKernel();

        $application = new Application(self::$kernel);

        $command = $application->find('app:csv:create-table');
        $commandTester = new CommandTester($command);

        $filePath = $this->createTemporaryCsv(
            <<<CSV
Name,Age,Grade,Salary
Alice Smith,29,L3,55000.50
Bob Johnson,34,L4,62000
Charlie Lee,41,L5,72000.75
CSV,
        );

        $commandTester->execute([
            'file' => $filePath,
            '--table' => 'employees',
        ]);

        $commandTester->assertCommandIsSuccessful();

        self::assertStringContainsString(
            <<<SQL
CREATE TABLE "employees" (
    "id" BIGSERIAL PRIMARY KEY,
    "name" VARCHAR(11) NOT NULL,
    "age" INTEGER NOT NULL,
    "grade" VARCHAR(2) NOT NULL,
    "salary" DECIMAL(15, 2) NOT NULL
);
SQL,
            $commandTester->getDisplay(),
        );
    }

    public function testItFailsWhenFileDoesNotExist(): void
    {
        self::bootKernel();

        $application = new Application(self::$kernel);

        $command = $application->find('app:csv:create-table');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'file' => '/tmp/not-existing-file.csv',
            '--table' => 'employees',
        ]);

        self::assertSame(1, $commandTester->getStatusCode());
        self::assertStringContainsString(
            'does not exist or is not readable',
            $commandTester->getDisplay(),
        );
    }

    private function createTemporaryCsv(string $content): string
    {
        $filePath = tempnam(sys_get_temp_dir(), 'csv_command_test_');

        if ($filePath === false) {
            self::fail('Failed to create temporary CSV file.');
        }

        file_put_contents($filePath, $content);

        return $filePath;
    }
}
