<?php

namespace App\Domain\Compensation\Http\Controllers;

use App\Domain\Compensation\Http\Requests\StoreCompensationChangeRequest;
use App\Domain\Compensation\Http\Resources\CompensationRecordResource;
use App\Domain\Compensation\Services\CompensationService;
use App\Domain\Employee\Models\Employment;
use App\Http\Controllers\Controller;

class CompensationController extends Controller
{
    public function __construct(private readonly CompensationService $compensation)
    {
    }

    public function index(Employment $employment)
    {
        $this->authorize('view', $employment);

        return CompensationRecordResource::collection(
            $employment->compensationRecords()->get()
        );
    }

    public function store(StoreCompensationChangeRequest $request, Employment $employment)
    {
        $record = $this->compensation->applyChange($employment, $request->validated());

        return CompensationRecordResource::make($record)->response()->setStatusCode(201);
    }
}
