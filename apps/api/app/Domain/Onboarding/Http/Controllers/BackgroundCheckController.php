<?php

namespace App\Domain\Onboarding\Http\Controllers;

use App\Domain\Employee\Models\Employment;
use App\Domain\Onboarding\Models\BackgroundCheck;
use App\Http\Controllers\Controller;

class BackgroundCheckController extends Controller
{
    public function index(Employment $employment)
    {
        $this->authorize('view', $employment);

        $checks = BackgroundCheck::where('employment_id', $employment->id)->get();

        return response()->json([
            'data' => $checks->map(fn ($c) => [
                'check_type' => $c->check_type,
                'status' => $c->status,
                'resolved_at' => $c->resolved_at?->toIso8601String(),
            ]),
        ]);
    }
}
