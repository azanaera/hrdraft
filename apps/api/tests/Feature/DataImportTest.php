<?php

use App\Domain\Employee\Models\Department;
use App\Domain\Employee\Models\Employment;
use App\Domain\Employee\Models\Location;
use App\Domain\Employee\Models\Position;
use App\Models\User;
use Illuminate\Http\UploadedFile;

function importCsv(string $goodDept, string $goodLoc, string $goodPosition): string
{
    return implode("\n", [
        'first_name,last_name,personal_email,employee_number,hire_date,employment_type,department_code,location_code,position_title,pay_type,rate_amount,pay_frequency',
        "Ada,Lovelace,ada@example.com,E-CSV1,2024-01-01,hourly,{$goodDept},{$goodLoc},{$goodPosition},hourly,20.00,biweekly",
        'Bad,Row,,,,,ZZZ-DEPT,ZZZ-LOC,Nonexistent,cash,abc,weekly',
    ]);
}

it('previews a CSV and flags row-level errors without writing anything', function () {
    $this->actingAs(User::factory()->admin()->create());

    $department = Department::factory()->create();
    $location = Location::factory()->create();
    $position = Position::factory()->create(['department_id' => $department->id]);

    $csv = importCsv($department->code, $location->code, $position->title);
    $file = UploadedFile::fake()->createWithContent('import.csv', $csv);

    $response = $this->postJson('/api/v1/admin/import/preview', ['file' => $file]);

    $response->assertOk();
    $response->assertJsonPath('valid_count', 1);
    $response->assertJsonPath('error_count', 1);
    expect(Employment::count())->toBe(0);
});

it('commits only valid rows, skipping invalid ones', function () {
    $this->actingAs(User::factory()->admin()->create());

    $department = Department::factory()->create();
    $location = Location::factory()->create();
    $position = Position::factory()->create(['department_id' => $department->id]);

    $csv = importCsv($department->code, $location->code, $position->title);
    $file = UploadedFile::fake()->createWithContent('import.csv', $csv);
    $preview = $this->postJson('/api/v1/admin/import/preview', ['file' => $file])->json();

    $response = $this->postJson('/api/v1/admin/import/commit', ['rows' => collect($preview['rows'])->pluck('data')->all()]);

    $response->assertCreated();
    expect($response->json('created'))->toHaveCount(1);
    expect($response->json('failed'))->toHaveCount(1);
    expect(Employment::where('employee_number', 'E-CSV1')->exists())->toBeTrue();
});
