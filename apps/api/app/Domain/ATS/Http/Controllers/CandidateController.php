<?php

namespace App\Domain\ATS\Http\Controllers;

use App\Domain\ATS\Http\Requests\StoreCandidateRequest;
use App\Domain\ATS\Http\Resources\ApplicationResource;
use App\Domain\ATS\Models\Application;
use App\Domain\ATS\Models\Candidate;
use App\Domain\ATS\Models\PipelineStage;
use App\Domain\Employee\Models\Person;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CandidateController extends Controller
{
    public function store(StoreCandidateRequest $request)
    {
        $application = DB::transaction(function () use ($request) {
            // Detect a candidate who is a former employee re-applying
            // (rehire-aware). Exact email match auto-links with confidence;
            // a name+DOB match without an email match is surfaced to HR as
            // "possible former employee, please confirm" rather than
            // auto-linked, since two different people can share a name.
            $linkedPerson = Person::where('personal_email', $request->input('email'))->first();

            $possibleFormerEmployee = null;
            if (! $linkedPerson && $request->filled('date_of_birth')) {
                $possibleFormerEmployee = Person::where('first_name', $request->string('first_name'))
                    ->where('last_name', $request->string('last_name'))
                    ->whereDate('date_of_birth', $request->date('date_of_birth'))
                    ->first();
            }

            $candidate = Candidate::create([
                'first_name' => $request->string('first_name'),
                'last_name' => $request->string('last_name'),
                'email' => $request->string('email'),
                'phone' => $request->input('phone'),
                'date_of_birth' => $request->input('date_of_birth'),
                'source' => $request->input('source'),
                'linked_person_id' => $linkedPerson?->id,
                'possible_former_employee_person_id' => $possibleFormerEmployee?->id,
            ]);

            $firstStage = PipelineStage::orderBy('order')->firstOrFail();

            $application = Application::create([
                'requisition_id' => $request->integer('requisition_id'),
                'candidate_id' => $candidate->id,
                'current_stage_id' => $firstStage->id,
                'status' => 'active',
                'applied_at' => now(),
            ]);

            $application->stageHistory()->create([
                'stage_id' => $firstStage->id,
                'entered_at' => now(),
                'moved_by_user_id' => auth()->id(),
            ]);

            return $application;
        });

        return ApplicationResource::make($application->load(['candidate', 'currentStage', 'requisition']))->response()->setStatusCode(201);
    }

    /**
     * HR confirms (or rejects) an auto-detected name+DOB possible-former-
     * employee match. Confirming promotes it to linked_person_id, the same
     * field an exact email match sets automatically.
     */
    public function confirmFormerEmployee(Request $request, Candidate $candidate)
    {
        abort_unless($request->user()->hasBackOfficeAccess(), 403);
        abort_if(! $candidate->possible_former_employee_person_id, 422, 'No possible former-employee match to confirm.');

        $confirmed = $request->boolean('confirmed', true);

        $candidate->update([
            'linked_person_id' => $confirmed ? $candidate->possible_former_employee_person_id : null,
            'possible_former_employee_person_id' => null,
        ]);

        return response()->json(['data' => $candidate->fresh()]);
    }
}
