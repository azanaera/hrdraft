<?php

namespace App\Domain\ATS\Http\Controllers;

use App\Domain\ATS\Http\Requests\HireApplicationRequest;
use App\Domain\ATS\Http\Resources\ApplicationResource;
use App\Domain\ATS\Models\Application;
use App\Domain\ATS\Models\PipelineStage;
use App\Domain\ATS\Services\HireCandidateService;
use App\Domain\Employee\Http\Resources\EmploymentResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApplicationController extends Controller
{
    public function __construct(private readonly HireCandidateService $hireCandidate)
    {
    }

    public function index(Request $request)
    {
        $query = Application::with(['candidate', 'currentStage', 'requisition']);

        if ($request->filled('requisition_id')) {
            $query->where('requisition_id', $request->integer('requisition_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return ApplicationResource::collection($query->latest('applied_at')->paginate(25));
    }

    public function moveStage(Request $request, Application $application)
    {
        $request->validate(['stage_id' => ['required', 'exists:pipeline_stages,id']]);

        DB::transaction(function () use ($request, $application) {
            $application->stageHistory()
                ->whereNull('exited_at')
                ->update(['exited_at' => now()]);

            $application->stageHistory()->create([
                'stage_id' => $request->integer('stage_id'),
                'entered_at' => now(),
                'moved_by_user_id' => $request->user()->id,
            ]);

            $application->update(['current_stage_id' => $request->integer('stage_id')]);
        });

        return ApplicationResource::make($application->fresh(['candidate', 'currentStage', 'requisition']));
    }

    public function reject(Request $request, Application $application)
    {
        $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        $application->update([
            'status' => 'rejected',
            'rejected_reason' => $request->input('reason'),
        ]);

        return ApplicationResource::make($application);
    }

    public function hire(HireApplicationRequest $request, Application $application)
    {
        $application = $this->hireCandidate->hire($application, $request->validated());

        return response()->json([
            'application' => ApplicationResource::make($application),
            'employment' => EmploymentResource::make($application->hiredEmployment->load(['person', 'currentAssignment', 'currentCompensation'])),
        ], 201);
    }
}
