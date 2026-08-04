<?php

namespace App\Domain\Onboarding\Http\Controllers;

use App\Domain\Onboarding\Models\OnboardingTask;
use App\Domain\Onboarding\Services\OnboardingWorkflowService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OnboardingTaskController extends Controller
{
    public function __construct(private readonly OnboardingWorkflowService $onboarding)
    {
    }

    public function complete(Request $request, OnboardingTask $task)
    {
        $task = $this->onboarding->completeTask($task, $request->integer('related_document_id') ?: null);

        return response()->json(['data' => $task]);
    }
}
