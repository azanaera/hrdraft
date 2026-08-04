<?php

namespace App\Domain\Employee\Http\Controllers;

use App\Domain\Employee\Http\Requests\BulkTransferRequest;
use App\Domain\Employee\Http\Requests\StoreEmploymentRequest;
use App\Domain\Employee\Http\Requests\TerminateEmploymentRequest;
use App\Domain\Employee\Http\Requests\TransferEmploymentRequest;
use App\Domain\Employee\Http\Resources\EmploymentResource;
use App\Domain\Employee\Models\Employment;
use App\Domain\Employee\Services\BulkTransferService;
use App\Domain\Employee\Services\HireService;
use App\Domain\Employee\Services\TerminationService;
use App\Domain\Employee\Services\TransferService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function __construct(
        private readonly HireService $hireService,
        private readonly TransferService $transferService,
        private readonly TerminationService $terminationService,
        private readonly BulkTransferService $bulkTransferService,
    ) {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Employment::class);

        $query = Employment::query()->with(['person', 'currentAssignment.department', 'currentAssignment.location', 'currentAssignment.position', 'currentCompensation']);

        if ($request->user()->role === 'people_manager') {
            $query->whereHas('currentAssignment', fn ($q) => $q->where('manager_employment_id', $request->user()->employment_id));
        } elseif ($request->user()->role === 'employee') {
            $query->where('id', $request->user()->employment_id);
        }

        if ($request->filled('status')) {
            $query->where('employment_status', $request->string('status'));
        }

        if ($request->filled('search')) {
            $term = '%'.$request->string('search').'%';
            $query->where(function ($q) use ($term) {
                $q->where('employee_number', 'ilike', $term)
                    ->orWhereHas('person', fn ($p) => $p->where('first_name', 'ilike', $term)->orWhere('last_name', 'ilike', $term));
            });
        }

        if ($request->filled('department_id')) {
            $query->whereHas('currentAssignment', fn ($q) => $q->where('department_id', $request->integer('department_id')));
        }

        if ($request->filled('location_id')) {
            $query->whereHas('currentAssignment', fn ($q) => $q->where('location_id', $request->integer('location_id')));
        }

        if ($request->filled('manager_employment_id')) {
            $query->whereHas('currentAssignment', fn ($q) => $q->where('manager_employment_id', $request->integer('manager_employment_id')));
        }

        return EmploymentResource::collection($query->paginate(25));
    }

    public function bulkTransfer(BulkTransferRequest $request)
    {
        $result = $this->bulkTransferService->transferMany(
            $request->input('employment_ids'),
            $request->only(['department_id', 'location_id', 'position_id', 'manager_employment_id', 'effective_start_date']),
        );

        return response()->json(['data' => $result]);
    }

    public function terminate(TerminateEmploymentRequest $request, Employment $employment)
    {
        $employment = $this->terminationService->terminate(
            $employment,
            $request->string('termination_date'),
            $request->string('reason'),
        );

        return EmploymentResource::make($employment->load(['person', 'currentAssignment']));
    }

    public function store(StoreEmploymentRequest $request)
    {
        $employment = $this->hireService->hire($request->validated());

        return EmploymentResource::make($employment)->response()->setStatusCode(201);
    }

    public function show(Employment $employment)
    {
        $this->authorize('view', $employment);

        $employment->load(['person', 'currentAssignment.department', 'currentAssignment.location', 'currentAssignment.position', 'currentCompensation']);

        return EmploymentResource::make($employment);
    }

    public function transfer(TransferEmploymentRequest $request, Employment $employment)
    {
        $assignment = $this->transferService->transfer($employment, $request->validated());

        return response()->json(['data' => $assignment->load(['department', 'location', 'position'])], 201);
    }
}
