# CSV to SQL Table Generator

A Symfony Console application that reads a CSV file, uses the header row as SQL column names, infers suitable SQL column types from the data rows, and outputs a `CREATE TABLE` statement.

The application does not create the table or insert data into a database. It only generates SQL output.

## Requirements

This project can be run fully through Docker and does not require a local PHP installation.

Required locally:

- Docker Desktop
- Git

The application runs on PHP 8.4 inside Docker.

## Setup

Build the PHP Docker image:

```bash
docker compose build
```

Install dependencies:

```bash
docker compose run --rm php composer install
```

## Usage

Run the command:

```bash
docker compose run --rm php php bin/console app:csv:create-table examples/employees.csv --table=employees
```

Optional custom delimiter:

```bash
docker compose run --rm php php bin/console app:csv:create-table examples/employees.csv --table=employees --delimiter=";"
```

## Example

Input CSV:

```csv
Name,Age,Grade,Salary
Alice Smith,29,L3,55000.50
Bob Johnson,34,L4,62000
Charlie Lee,41,L5,72000.75
Diana Patel,27,L2,48000
Edward Kim,38,L4,67000.25
Fiona Brown,25,L1,53000
George White,32,L3,58900.80
Hannah Black,36,L4,65000
```

Output:

```sql
CREATE TABLE "employees" (
    "id" BIGSERIAL PRIMARY KEY,
    "name" VARCHAR(12) NOT NULL,
    "age" INTEGER NOT NULL,
    "grade" VARCHAR(2) NOT NULL,
    "salary" DECIMAL(15, 2) NOT NULL
);
```

## Running tests

Run all tests:

```bash
docker compose run --rm php vendor/bin/phpunit tests
```

Run only unit tests:

```bash
docker compose run --rm php vendor/bin/phpunit tests/Unit
```

Run only functional tests:

```bash
docker compose run --rm php vendor/bin/phpunit tests/Functional
```

## Project structure

```text
src/
  Command/
    GenerateCreateTableCommand.php

  Csv/
    CsvReader.php
    CsvTable.php

  Sql/
    ColumnDefinition.php
    SqlIdentifierNormalizer.php
    SqlTypeInferer.php
    SqlCreateTableGenerator.php

tests/
  Unit/
    Csv/
      CsvReaderTest.php

    Sql/
      SqlIdentifierNormalizerTest.php
      SqlTypeInfererTest.php
      SqlCreateTableGeneratorTest.php

  Functional/
    Command/
      GenerateCreateTableCommandTest.php

examples/
  employees.csv

elasticsearch/
  employees.mapping.json
  employees.document.example.json
  employees.search.example.json
```

## Design decisions

The Symfony command is intentionally kept thin. It is responsible for reading command-line arguments, validating the input file, and printing the generated SQL output.

The main logic is delegated to dedicated services:

- `CsvReader` reads the CSV file and converts it into headers and rows.
- `SqlIdentifierNormalizer` converts CSV headers into safe SQL identifiers.
- `SqlTypeInferer` infers SQL column types from the CSV data.
- `SqlCreateTableGenerator` generates the final `CREATE TABLE` statement.

This keeps the code simple, testable, and easy to extend without introducing unnecessary architectural complexity for a single read-transform-output workflow.

## Assumptions

The generated SQL follows a PostgreSQL-style syntax.

Each generated table always includes an auto-generated technical primary key:

```sql
"id" BIGSERIAL PRIMARY KEY
```

The CSV data may not provide a stable unique identifier, so the generated schema adds a technical primary key suitable for relational storage.

Column names are normalized to lowercase snake_case format. For example:

```text
First Name -> first_name
Employee-ID -> employee_id
123 Code -> _123_code
```

The application currently infers the following SQL types:

- `INTEGER`
- `DECIMAL(15, 2)`
- `BOOLEAN`
- `DATE`
- `VARCHAR(n)`
- `TEXT`

Empty values make the generated column nullable.

## Elasticsearch extra credit

The `elasticsearch/` directory contains example files showing how employee data could be indexed into Elasticsearch or another open search engine.

Files:

```text
elasticsearch/employees.mapping.json
elasticsearch/employees.document.example.json
elasticsearch/employees.search.example.json
```

The example document contains structured employee data:

```json
{
  "id": 1,
  "name": "Alice Smith",
  "age": 29,
  "grade": "L3",
  "salary": 55000.5,
  "source_table": "employees",
  "updated_at": "2026-05-23T10:30:00+00:00"
}
```

The `name` field is mapped as `text` because it should support full-text search. It also has a `keyword` subfield for exact sorting or filtering.

Fields such as `grade`, `age`, and `salary` should not use fuzzy matching. They represent structured data and are better handled through exact filters or range filters.

The `name` field can support fuzzy matching because employee names are commonly searched manually and may contain typos, missing characters, or slightly different spelling.

For CSV files with 100k+ rows, indexing should be performed in batches using the Elasticsearch Bulk API. The application should avoid loading the whole dataset into memory at once and should process rows as a stream or in chunks.

## Example Elasticsearch search

The example search query demonstrates:

- fuzzy matching by employee name
- filtering by grade
- salary range filtering
- custom sorting by salary and name

```json
{
  "query": {
    "bool": {
      "must": [
        {
          "match": {
            "name": {
              "query": "Alic Smith",
              "fuzziness": "AUTO"
            }
          }
        }
      ],
      "filter": [
        {
          "term": {
            "grade": "L3"
          }
        },
        {
          "range": {
            "salary": {
              "gte": 50000
            }
          }
        }
      ]
    }
  },
  "sort": [
    {
      "salary": {
        "order": "desc"
      }
    },
    {
      "name.keyword": {
        "order": "asc"
      }
    }
  ]
}
```

## Keeping SQL and Elasticsearch in sync

In production, I would keep the SQL database as the source of truth and update Elasticsearch asynchronously. When a user updates an employee’s salary through the web interface, the application should save the salary change and create an outbox event in the same database transaction, for example `EmployeeSalaryUpdated`. A background worker would then read unpublished outbox events and update the corresponding employee document in Elasticsearch. This avoids coupling the user-facing request directly to Elasticsearch availability and prevents data loss if Elasticsearch is temporarily unavailable. The worker should be idempotent, so processing the same event more than once produces the same final indexed document. Failed indexing attempts should be retried with exponential backoff and eventually moved to a dead-letter queue or error table for investigation. For larger datasets, I would also provide a reindex command that reads employees from SQL in batches and uses Elasticsearch bulk indexing. This architecture gives eventual consistency while keeping SQL reliable for writes and Elasticsearch optimized for search.

## Possible improvements

Possible future improvements:

- support different SQL dialects, such as MySQL or SQLite
- allow choosing whether to generate an `id` column
- support custom type inference rules
- support streaming type inference for very large CSV files
- generate Elasticsearch mappings dynamically from inferred SQL column types
- add code style checks with PHP-CS-Fixer
- add static analysis with PHPStan
