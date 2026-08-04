<?php

namespace App\Domain\Employee\Http\Controllers;

use App\Domain\Employee\Http\Requests\RehireRequest;
use App\Domain\Employee\Http\Resources\EmploymentResource;
use App\Domain\Employee\Models\Person;
use App\Domain\Employee\Services\RehireService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PersonController extends Controller
{
    public function __construct(private readonly RehireService $rehireService)
    {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', \App\Domain\Employee\Models\Employment::class);

        $people = Person::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(fn ($q) => $q->where('first_name', 'ilike', $term)->orWhere('last_name', 'ilike', $term)->orWhere('person_number', 'ilike', $term));
            })
            ->with('employments')
            ->paginate(25);

        return response()->json($people);
    }

    public function rehire(RehireRequest $request, Person $person)
    {
        $employment = $this->rehireService->rehire($person, $request->validated());

        return EmploymentResource::make($employment->load(['person']))->response()->setStatusCode(201);
    }
}
