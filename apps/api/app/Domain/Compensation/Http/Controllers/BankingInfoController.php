<?php

namespace App\Domain\Compensation\Http\Controllers;

use App\Domain\Compensation\Http\Requests\StoreBankingInfoRequest;
use App\Domain\Compensation\Http\Resources\BankingInfoResource;
use App\Domain\Compensation\Models\EmploymentBankingInfo;
use App\Domain\Compensation\Services\BankingInfoService;
use App\Domain\Employee\Models\Employment;
use App\Http\Controllers\Controller;

class BankingInfoController extends Controller
{
    public function __construct(private readonly BankingInfoService $bankingInfo)
    {
    }

    public function show(Employment $employment)
    {
        $this->authorize('view', $employment);

        $info = EmploymentBankingInfo::where('employment_id', $employment->id)->first();

        return response()->json(['data' => BankingInfoResource::make($info)]);
    }

    public function store(StoreBankingInfoRequest $request, Employment $employment)
    {
        $info = $this->bankingInfo->capture(
            $employment,
            $request->string('routing_number'),
            $request->string('account_number'),
            $request->string('account_type'),
        );

        return BankingInfoResource::make($info)->response()->setStatusCode(201);
    }
}
