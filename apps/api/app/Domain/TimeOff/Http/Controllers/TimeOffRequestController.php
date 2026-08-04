<?php

namespace App\Domain\TimeOff\Http\Controllers;

use App\Domain\TimeOff\Http\Requests\DecideTimeOffRequestRequest;
use App\Domain\TimeOff\Http\Requests\StoreTimeOffRequestRequest;
use App\Domain\TimeOff\Http\Resources\TimeOffRequestResource;
use App\Domain\TimeOff\Models\TimeOffPolicy;
use App\Domain\TimeOff\Models\TimeOffRequest as TimeOffRequestModel;
use App\Domain\TimeOff\Services\TimeOffService;
use App\Http\Controllers\Controller;
use App\Domain\Employee\Models\Employment;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TimeOffRequestController extends Controller
{
    public function __construct(private readonly TimeOffService $timeOff)
    {
    }

    public function index(Request $request)
    {
        $query = TimeOffRequestModel::query()->with(['employment.person', 'policy', 'decidedBy']);

        if ($request->user()->role === 'employee') {
            $query->where('employment_id', $request->user()->employment_id);
        } elseif ($request->user()->role === 'people_manager') {
            $query->whereHas('employment.currentAssignment', fn ($q) => $q->where('manager_employment_id', $request->user()->employment_id));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return TimeOffRequestResource::collection($query->orderByDesc('requested_at')->paginate(25));
    }

    public function store(StoreTimeOffRequestRequest $request)
    {
        $employment = Employment::findOrFail($request->integer('employment_id'));

        if ($request->user()->role === 'employee' && $request->user()->employment_id !== $employment->id) {
            throw new HttpException(403, 'Cannot request time off on behalf of another employee.');
        }

        $policy = TimeOffPolicy::findOrFail($request->integer('policy_id'));

        $timeOffRequest = $this->timeOff->submitRequest($employment, $policy, $request->validated());

        return TimeOffRequestResource::make($timeOffRequest)->response()->setStatusCode(201);
    }

    public function approve(DecideTimeOffRequestRequest $request, TimeOffRequestModel $timeOffRequest)
    {
        $this->assertCanDecide($request, $timeOffRequest);

        $result = $this->timeOff->approve($timeOffRequest, $request->user(), $request->input('notes'));

        return TimeOffRequestResource::make($result->load(['employment.person', 'policy']));
    }

    public function deny(DecideTimeOffRequestRequest $request, TimeOffRequestModel $timeOffRequest)
    {
        $this->assertCanDecide($request, $timeOffRequest);

        $result = $this->timeOff->deny($timeOffRequest, $request->user(), $request->input('notes'));

        return TimeOffRequestResource::make($result->load(['employment.person', 'policy']));
    }

    private function assertCanDecide(Request $request, TimeOffRequestModel $timeOffRequest): void
    {
        if (! $this->timeOff->canDecide($timeOffRequest->employment, $request->user())) {
            throw new HttpException(403, 'Not authorized to decide this request.');
        }
    }
}
