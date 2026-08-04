<?php

namespace Database\Seeders;

use App\Domain\Documents\Models\DocumentCategory;
use App\Domain\Employee\Models\Department;
use App\Domain\Employee\Models\Location;
use App\Domain\Employee\Models\Position;
use App\Domain\Employee\Services\HireService;
use App\Domain\Offboarding\Models\OffboardingTemplate;
use App\Domain\Onboarding\Models\OnboardingTemplate;
use App\Domain\TimeOff\Models\TimeOffPolicy;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $hq = Location::where('code', 'HQ-01')->first();
        $warehouse = Location::where('code', 'DAL-01')->first();

        $hrDept = Department::firstOrCreate(['code' => 'DEPT-HR'], ['name' => 'Human Resources', 'is_active' => true]);
        $opsDept = Department::firstOrCreate(['code' => 'DEPT-OPS'], ['name' => 'Warehouse Operations', 'is_active' => true]);

        $hrPosition = Position::firstOrCreate(
            ['title' => 'HR Manager', 'department_id' => $hrDept->id],
            ['default_employment_type' => 'salaried', 'is_active' => true]
        );
        $associatePosition = Position::firstOrCreate(
            ['title' => 'Warehouse Associate', 'department_id' => $opsDept->id],
            ['default_employment_type' => 'hourly', 'is_active' => true]
        );

        // Admin (no employment record needed — back office system access only).
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'System Admin', 'password' => Hash::make('password'), 'role' => 'admin', 'email_verified_at' => now()]
        );

        // HR Manager, hired through the normal HireService so it has a real employment record.
        $hrManagerEmployment = app(HireService::class)->hire([
            'first_name' => 'Jamie', 'last_name' => 'Rivera',
            'personal_email' => 'jamie.rivera@example.com',
            'employee_number' => 'E-HRMGR1', 'hire_date' => now()->subYears(2)->toDateString(),
            'employment_type' => 'salaried',
            'department_id' => $hrDept->id, 'location_id' => $hq->id, 'position_id' => $hrPosition->id,
            'pay_type' => 'salary', 'rate_amount' => 78000, 'pay_frequency' => 'annual',
        ]);
        $hrManagerUser = User::firstOrCreate(
            ['email' => 'hr.manager@example.com'],
            ['name' => 'Jamie Rivera', 'password' => Hash::make('password'), 'role' => 'hr_manager', 'employment_id' => $hrManagerEmployment->id, 'email_verified_at' => now()]
        );

        // People/Field Manager, oversees warehouse associates.
        $peopleManagerEmployment = app(HireService::class)->hire([
            'first_name' => 'Morgan', 'last_name' => 'Lee',
            'personal_email' => 'morgan.lee@example.com',
            'employee_number' => 'E-PPLMGR1', 'hire_date' => now()->subYear()->toDateString(),
            'employment_type' => 'salaried',
            'department_id' => $opsDept->id, 'location_id' => $warehouse->id, 'position_id' => $associatePosition->id,
            'pay_type' => 'salary', 'rate_amount' => 58000, 'pay_frequency' => 'annual',
        ]);
        $peopleManagerUser = User::firstOrCreate(
            ['email' => 'people.manager@example.com'],
            ['name' => 'Morgan Lee', 'password' => Hash::make('password'), 'role' => 'people_manager', 'employment_id' => $peopleManagerEmployment->id, 'email_verified_at' => now()]
        );

        // Rank-and-file hourly employee, reporting to the people manager.
        $employeeEmployment = app(HireService::class)->hire([
            'first_name' => 'Casey', 'last_name' => 'Nguyen',
            'personal_email' => 'casey.nguyen@example.com',
            'employee_number' => 'E-ASSOC1', 'hire_date' => now()->subMonths(4)->toDateString(),
            'employment_type' => 'hourly',
            'department_id' => $opsDept->id, 'location_id' => $warehouse->id, 'position_id' => $associatePosition->id,
            'manager_employment_id' => $peopleManagerEmployment->id,
            'pay_type' => 'hourly', 'rate_amount' => 19.50, 'pay_frequency' => 'biweekly',
        ]);
        User::firstOrCreate(
            ['email' => 'casey.nguyen@example.com'],
            ['name' => 'Casey Nguyen', 'password' => Hash::make('password'), 'role' => 'employee', 'employment_id' => $employeeEmployment->id, 'email_verified_at' => now()]
        );

        foreach (['PTO', 'Sick Leave'] as $policyName) {
            TimeOffPolicy::firstOrCreate(
                ['name' => $policyName],
                ['applies_to' => 'all', 'accrual_method' => 'per_pay_period', 'accrual_rate' => 3.0, 'max_balance' => 120, 'is_active' => true]
            );
        }

        foreach (['I-9', 'W-4', 'Offer Letter', 'Handbook Acknowledgment'] as $categoryName) {
            DocumentCategory::firstOrCreate(['name' => $categoryName], ['applicable_to' => 'all', 'requires_signature' => $categoryName === 'Handbook Acknowledgment']);
        }

        $template = OnboardingTemplate::firstOrCreate(
            ['name' => 'Standard Hourly Onboarding'],
            ['applicable_employment_type' => 'hourly', 'is_active' => true]
        );
        if ($template->tasks()->count() === 0) {
            foreach ([
                ['title' => 'Complete I-9', 'task_type' => 'document_upload', 'order' => 1],
                ['title' => 'Complete W-4', 'task_type' => 'document_upload', 'order' => 2],
                ['title' => 'Acknowledge Employee Handbook', 'task_type' => 'document_ack', 'order' => 3],
                ['title' => 'Provision badge/system access', 'task_type' => 'provisioning', 'order' => 4],
            ] as $task) {
                $template->tasks()->create($task + ['is_required' => true]);
            }
        }

        $offboardingTemplate = OffboardingTemplate::firstOrCreate(
            ['name' => 'Standard Offboarding'],
            ['is_active' => true]
        );
        if ($offboardingTemplate->tasks()->count() === 0) {
            foreach ([
                ['title' => 'Confirm system access revoked', 'task_type' => 'access_revocation', 'order' => 1],
                ['title' => 'Collect badge/keys/equipment', 'task_type' => 'equipment_return', 'order' => 2],
                ['title' => 'Conduct exit interview', 'task_type' => 'exit_interview', 'order' => 3],
                ['title' => 'Confirm final payout calculated', 'task_type' => 'final_payout', 'order' => 4],
            ] as $task) {
                $offboardingTemplate->tasks()->create($task + ['is_required' => true]);
            }
        }

        $this->command?->info('Demo logins (password: "password"): admin@example.com, hr.manager@example.com, people.manager@example.com, casey.nguyen@example.com');
    }
}
