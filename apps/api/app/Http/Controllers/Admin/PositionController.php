<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Employee\Models\Position;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PositionController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->hasBackOfficeAccess(), 403);

        return response()->json(['data' => Position::with('department')->orderBy('title')->get()]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->hasBackOfficeAccess(), 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'department_id' => ['required', 'exists:departments,id'],
            'default_employment_type' => ['required', 'in:hourly,salaried'],
            'description' => ['nullable', 'string'],
        ]);

        $position = Position::create($data + ['is_active' => true]);

        return response()->json(['data' => $position], 201);
    }

    public function update(Request $request, Position $position)
    {
        abort_unless($request->user()->hasBackOfficeAccess(), 403);

        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'default_employment_type' => ['sometimes', 'in:hourly,salaried'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $position->update($data);

        return response()->json(['data' => $position]);
    }
}
