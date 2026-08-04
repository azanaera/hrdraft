<?php

namespace App\Domain\TimeOff\Http\Controllers;

use App\Domain\TimeOff\Models\TimeOffPolicy;
use App\Http\Controllers\Controller;

class TimeOffPolicyController extends Controller
{
    public function index()
    {
        return response()->json(['data' => TimeOffPolicy::where('is_active', true)->get()]);
    }
}
