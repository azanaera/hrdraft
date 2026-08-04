<?php

namespace App\Domain\Employee\Services;

use App\Domain\Employee\Models\Department;
use App\Domain\Employee\Models\Location;
use App\Domain\Employee\Models\Position;
use Illuminate\Support\Facades\DB;

/**
 * One-time spreadsheet-to-database import for the existing employee data
 * (currently in Excel/CSV/Google Sheets, per the spec). Deliberately not
 * built on HireService — spreadsheet rows won't map 1:1 onto a clean "hire"
 * call, and importing needs row-level validation/reporting that a normal
 * hire flow doesn't.
 *
 * Expected CSV columns: first_name, last_name, personal_email,
 * employee_number, hire_date, employment_type, department_code,
 * location_code, position_title, pay_type, rate_amount, pay_frequency
 */
class SpreadsheetImportService
{
    private const REQUIRED_COLUMNS = [
        'first_name', 'last_name', 'employee_number', 'hire_date', 'employment_type',
        'department_code', 'location_code', 'position_title', 'pay_type', 'rate_amount', 'pay_frequency',
    ];

    public function __construct(
        private readonly HireService $hireService,
    ) {
    }

    /**
     * Parses the file and validates every row without writing anything.
     */
    public function preview(string $csvContents): array
    {
        $rows = $this->parseRows($csvContents);
        $seenEmployeeNumbers = [];

        foreach ($rows as &$row) {
            $row['errors'] = $this->validateRow($row['data'], $seenEmployeeNumbers);
            $row['valid'] = empty($row['errors']);

            if ($row['valid']) {
                $seenEmployeeNumbers[] = $row['data']['employee_number'];
            }
        }

        return [
            'rows' => $rows,
            'valid_count' => count(array_filter($rows, fn ($r) => $r['valid'])),
            'error_count' => count(array_filter($rows, fn ($r) => ! $r['valid'])),
        ];
    }

    /**
     * Re-validates and commits every valid row. Each row is its own
     * transaction (mirrors BulkTransferService's per-item isolation) so one
     * bad row doesn't abort the whole batch.
     */
    public function commit(array $rows): array
    {
        $created = [];
        $failed = [];
        $seenEmployeeNumbers = [];

        foreach ($rows as $index => $data) {
            $errors = $this->validateRow($data, $seenEmployeeNumbers);

            if (! empty($errors)) {
                $failed[] = ['row' => $index, 'errors' => $errors];

                continue;
            }

            $seenEmployeeNumbers[] = $data['employee_number'];

            try {
                $employment = DB::transaction(fn () => $this->importRow($data));
                $created[] = ['row' => $index, 'employment_id' => $employment->id];
            } catch (\Throwable $e) {
                $failed[] = ['row' => $index, 'errors' => [$e->getMessage()]];
            }
        }

        return ['created' => $created, 'failed' => $failed];
    }

    private function importRow(array $data): \App\Domain\Employee\Models\Employment
    {
        $department = Department::where('code', $data['department_code'])->firstOrFail();
        $location = Location::where('code', $data['location_code'])->firstOrFail();
        $position = Position::where('title', $data['position_title'])->where('department_id', $department->id)->firstOrFail();

        return $this->hireService->hire([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'personal_email' => $data['personal_email'] ?? null,
            'employee_number' => $data['employee_number'],
            'hire_date' => $data['hire_date'],
            'employment_type' => $data['employment_type'],
            'department_id' => $department->id,
            'location_id' => $location->id,
            'position_id' => $position->id,
            'pay_type' => $data['pay_type'],
            'rate_amount' => $data['rate_amount'],
            'pay_frequency' => $data['pay_frequency'],
        ]);
    }

    private function validateRow(array $data, array $seenEmployeeNumbers): array
    {
        $errors = [];

        foreach (self::REQUIRED_COLUMNS as $column) {
            if (empty($data[$column])) {
                $errors[] = "Missing required field: {$column}";
            }
        }

        if (! empty($data['employee_number'])) {
            if (in_array($data['employee_number'], $seenEmployeeNumbers, true)) {
                $errors[] = "Duplicate employee_number within file: {$data['employee_number']}";
            } elseif (\App\Domain\Employee\Models\Employment::where('employee_number', $data['employee_number'])->exists()) {
                $errors[] = "employee_number already exists: {$data['employee_number']}";
            }
        }

        if (! empty($data['employment_type']) && ! in_array($data['employment_type'], ['hourly', 'salaried'], true)) {
            $errors[] = "Invalid employment_type: {$data['employment_type']} (expected hourly or salaried)";
        }

        if (! empty($data['pay_type']) && ! in_array($data['pay_type'], ['hourly', 'salary'], true)) {
            $errors[] = "Invalid pay_type: {$data['pay_type']} (expected hourly or salary)";
        }

        if (! empty($data['department_code']) && ! Department::where('code', $data['department_code'])->exists()) {
            $errors[] = "Unknown department_code: {$data['department_code']}";
        }

        if (! empty($data['location_code']) && ! Location::where('code', $data['location_code'])->exists()) {
            $errors[] = "Unknown location_code: {$data['location_code']}";
        }

        if (! empty($data['hire_date']) && ! strtotime($data['hire_date'])) {
            $errors[] = "Invalid hire_date: {$data['hire_date']}";
        }

        return $errors;
    }

    private function parseRows(string $csvContents): array
    {
        $lines = array_values(array_filter(preg_split('/\r\n|\r|\n/', $csvContents), fn ($l) => trim($l) !== ''));

        if (empty($lines)) {
            return [];
        }

        $header = str_getcsv(array_shift($lines));
        $header = array_map(fn ($h) => trim(strtolower($h)), $header);

        $rows = [];
        foreach ($lines as $line) {
            $values = str_getcsv($line);
            $data = [];
            foreach ($header as $i => $column) {
                $data[$column] = isset($values[$i]) ? trim($values[$i]) : null;
            }
            $rows[] = ['data' => $data];
        }

        return $rows;
    }
}
