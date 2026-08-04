<?php

namespace App\Console\Commands;

use App\Domain\Employee\Services\SpreadsheetImportService;
use Illuminate\Console\Command;

class ImportEmployeesCommand extends Command
{
    protected $signature = 'hris:import {path : Path to the CSV file} {--dry-run : Validate only, do not write anything}';

    protected $description = 'Import employees from a spreadsheet (CSV) — the one-time data migration path.';

    public function handle(SpreadsheetImportService $importService): int
    {
        $path = $this->argument('path');

        if (! file_exists($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        $contents = file_get_contents($path);
        $preview = $importService->preview($contents);

        $this->info("Parsed {$this->rowCount($preview)} rows — {$preview['valid_count']} valid, {$preview['error_count']} with errors.");

        foreach ($preview['rows'] as $i => $row) {
            if (! $row['valid']) {
                $this->warn("Row {$i}: ".implode('; ', $row['errors']));
            }
        }

        if ($this->option('dry-run')) {
            $this->info('Dry run — nothing was written.');

            return self::SUCCESS;
        }

        if ($preview['error_count'] > 0 && ! $this->confirm('Some rows have errors and will be skipped. Continue with the valid rows?', true)) {
            return self::FAILURE;
        }

        $validRows = array_map(fn ($row) => $row['data'], array_filter($preview['rows'], fn ($row) => $row['valid']));
        $result = $importService->commit($validRows);

        $this->info(count($result['created']).' employee(s) created.');

        foreach ($result['failed'] as $failure) {
            $this->warn("Row {$failure['row']} failed on commit: ".implode('; ', $failure['errors']));
        }

        return self::SUCCESS;
    }

    private function rowCount(array $preview): int
    {
        return count($preview['rows']);
    }
}
