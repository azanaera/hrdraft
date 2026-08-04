<?php

namespace App\Domain\Onboarding\Http\Controllers;

use App\Domain\Employee\Models\Employment;
use App\Domain\Onboarding\Http\Resources\OnboardingWorkflowResource;
use App\Domain\Onboarding\Models\OnboardingTemplate;
use App\Domain\Onboarding\Models\OnboardingWorkflow;
use App\Domain\Onboarding\Services\OnboardingWorkflowService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OnboardingWorkflowController extends Controller
{
    public function __construct(private readonly OnboardingWorkflowService $onboarding)
    {
    }

    public function show(Employment $employment)
    {
        $workflow = OnboardingWorkflow::where('employment_id', $employment->id)
            ->with(['template', 'tasks'])
            ->latest()
            ->first();

        return $workflow
            ? OnboardingWorkflowResource::make($workflow)
            : response()->json(['data' => null]);
    }

    public function store(Request $request, Employment $employment)
    {
        $this->authorize('create', \App\Domain\Employee\Models\Employment::class);

        $request->validate(['template_id' => ['required', 'exists:onboarding_templates,id']]);

        $template = OnboardingTemplate::with('tasks')->findOrFail($request->integer('template_id'));

        $workflow = $this->onboarding->start($employment, $template);

        return OnboardingWorkflowResource::make($workflow)->response()->setStatusCode(201);
    }
}
