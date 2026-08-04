<?php

namespace App\Domain\Reporting\Http\Controllers;

use App\Domain\Reporting\Services\DashboardService;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function show(DashboardService $dashboard)
    {
        return response()->json(['data' => $dashboard->summary()]);
    }
}
