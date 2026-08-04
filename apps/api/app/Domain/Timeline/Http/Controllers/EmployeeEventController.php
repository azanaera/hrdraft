<?php

namespace App\Domain\Timeline\Http\Controllers;

use App\Domain\Employee\Models\Person;
use App\Domain\Timeline\Http\Requests\StoreNoteRequest;
use App\Domain\Timeline\Http\Resources\EmployeeEventResource;
use App\Domain\Timeline\Services\TimelineRecorder;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EmployeeEventController extends Controller
{
    public function __construct(private readonly TimelineRecorder $timeline)
    {
    }

    public function index(Request $request, Person $person)
    {
        $this->authorize('view', $person->currentEmployment ?? $person->employments()->first());

        $events = $person->events()
            ->with('actor')
            ->when(
                ! $request->user()->hasBackOfficeAccess(),
                fn ($q) => $q->where('visibility', '!=', 'admin_only')
            )
            ->orderByDesc('event_date')
            ->orderByDesc('id')
            ->paginate(25);

        return EmployeeEventResource::collection($events);
    }

    public function store(StoreNoteRequest $request, Person $person)
    {
        $event = $this->timeline->record(
            person: $person,
            employment: $person->currentEmployment ?? $person->employments()->first(),
            eventType: 'note',
            summary: $request->string('summary'),
            visibility: $request->input('visibility', 'manager_and_above'),
            eventDate: $request->input('event_date'),
        );

        return EmployeeEventResource::make($event)->response()->setStatusCode(201);
    }
}
