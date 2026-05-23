<?php

declare(strict_types=1);

namespace App\Command;

use App\Csv\CsvReader;
use App\Sql\SqlCreateTableGenerator;
use App\Sql\SqlIdentifierNormalizer;
use App\Sql\SqlTypeInferer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:csv:create-table',
    description: 'Generates an SQL CREATE TABLE statement from a CSV file.'
)]
final class GenerateCreateTableCommand extends Command
{
    public function __construct(
        private readonly CsvReader $csvReader,
        private readonly SqlTypeInferer $typeInferer,
        private readonly SqlCreateTableGenerator $tableGenerator,
        private readonly SqlIdentifierNormalizer $identifierNormalizer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'Path to the CSV file')
            ->addOption('table', 't', InputOption::VALUE_REQUIRED, 'SQL table name')
            ->addOption('delimiter', 'd', InputOption::VALUE_REQUIRED, 'CSV delimiter', ',');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $filePath = (string) $input->getArgument('file');
        $delimiter = (string) $input->getOption('delimiter');

        if (!is_file($filePath) || !is_readable($filePath)) {
            $io->error(sprintf('File "%s" does not exist or is not readable.', $filePath));

            return Command::FAILURE;
        }

        $tableName = $input->getOption('table')
            ? (string) $input->getOption('table')
            : pathinfo($filePath, PATHINFO_FILENAME);

        try {
            $csvTable = $this->csvReader->read($filePath, $delimiter);

            $columns = $this->typeInferer->infer($csvTable->headers, $csvTable->rows);

            $sql = $this->tableGenerator->generate(
                $this->identifierNormalizer->normalize($tableName),
                $columns
            );

            $io->writeln($sql);

            return Command::SUCCESS;
        } catch (\Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }
    }
}
