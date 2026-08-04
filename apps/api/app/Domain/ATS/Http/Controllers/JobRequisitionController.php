<?php

namespace App\Domain\ATS\Http\Controllers;

use App\Domain\ATS\Http\Requests\StoreRequisitionRequest;
use App\Domain\ATS\Http\Resources\JobRequisitionResource;
use App\Domain\ATS\Models\JobRequisition;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JobRequisitionController extends Controller
{
    public function index(Request $request)
    {
        $query = JobRequisition::with(['department', 'location', 'hiringManager'])->withCount('applications');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return JobRequisitionResource::collection($query->latest()->paginate(25));
    }

    public function store(StoreRequisitionRequest $request)
    {
        $requisition = JobRequisition::create([
            ...$request->validated(),
            'status' => $request->input('status', 'draft'),
            'created_by_user_id' => Auth::id(),
            'opened_at' => $request->input('status') === 'open' ? now() : null,
        ]);

        return JobRequisitionResource::make($requisition->load(['department', 'location']))->response()->setStatusCode(201);
    }

    public function show(JobRequisition $requisition)
    {
        return JobRequisitionResource::make($requisition->load(['department', 'location', 'hiringManager']));
    }

    public function update(StoreRequisitionRequest $request, JobRequisition $requisition)
    {
        $requisition->update($request->validated());

        return JobRequisitionResource::make($requisition->fresh(['department', 'location']));
    }
}
