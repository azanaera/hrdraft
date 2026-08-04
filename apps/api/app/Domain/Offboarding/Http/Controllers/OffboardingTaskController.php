<?php

namespace App\Domain\Offboarding\Http\Controllers;

use App\Domain\Offboarding\Models\OffboardingTask;
use App\Domain\Offboarding\Services\OffboardingWorkflowService;
use App\Http\Controllers\Controller;

class OffboardingTaskController extends Controller
{
    public function __construct(private readonly OffboardingWorkflowService $offboarding)
    {
    }

    public function complete(OffboardingTask $task)
    {
        $task = $this->offboarding->completeTask($task);

        return response()->json(['data' => $task]);
    }
}
