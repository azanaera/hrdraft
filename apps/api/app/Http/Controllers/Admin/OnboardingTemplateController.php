<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Onboarding\Models\OnboardingTemplate;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OnboardingTemplateController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->hasBackOfficeAccess(), 403);

        return response()->json(['data' => OnboardingTemplate::with('tasks')->orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->hasBackOfficeAccess(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'applicable_employment_type' => ['nullable', 'in:hourly,salaried'],
        ]);

        $template = OnboardingTemplate::create($data + ['is_active' => true]);

        return response()->json(['data' => $template], 201);
    }

    public function addTask(Request $request, OnboardingTemplate $onboardingTemplate)
    {
        abort_unless($request->user()->hasBackOfficeAccess(), 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'task_type' => ['required', 'in:form,document_upload,document_ack,provisioning,generic'],
            'order' => ['nullable', 'integer', 'min:0'],
            'is_required' => ['boolean'],
        ]);

        $task = $onboardingTemplate->tasks()->create($data + ['order' => $data['order'] ?? 0]);

        return response()->json(['data' => $task], 201);
    }
}
