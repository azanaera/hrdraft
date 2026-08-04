<?php

namespace App\Domain\Offboarding\Http\Controllers;

use App\Domain\Employee\Models\Employment;
use App\Domain\Offboarding\Http\Resources\OffboardingWorkflowResource;
use App\Domain\Offboarding\Models\OffboardingWorkflow;
use App\Http\Controllers\Controller;

class OffboardingWorkflowController extends Controller
{
    public function show(Employment $employment)
    {
        $this->authorize('view', $employment);

        $workflow = OffboardingWorkflow::where('employment_id', $employment->id)
            ->with(['template', 'tasks'])
            ->latest()
            ->first();

        return $workflow
            ? OffboardingWorkflowResource::make($workflow)
            : response()->json(['data' => null]);
    }
}
