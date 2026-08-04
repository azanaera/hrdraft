<?php

namespace App\Domain\Onboarding\Http\Controllers;

use App\Domain\Onboarding\Models\OnboardingTemplate;
use App\Http\Controllers\Controller;

class OnboardingTemplateController extends Controller
{
    public function index()
    {
        return response()->json(['data' => OnboardingTemplate::with('tasks')->where('is_active', true)->get()]);
    }
}
