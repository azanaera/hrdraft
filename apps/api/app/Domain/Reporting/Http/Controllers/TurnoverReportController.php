<?php

namespace App\Domain\Reporting\Http\Controllers;

use App\Domain\Reporting\Services\TurnoverReportService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TurnoverReportController extends Controller
{
    public function __construct(private readonly TurnoverReportService $reportService)
    {
    }

    public function show(Request $request)
    {
        abort_unless($request->user()->hasBackOfficeAccess(), 403);

        $data = $this->reportService->summary($request->input('from'), $request->input('to'));

        return response()->json(['data' => $data]);
    }
}
