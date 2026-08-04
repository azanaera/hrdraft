<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Employee\Models\Department;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->hasBackOfficeAccess(), 403);

        return response()->json(['data' => Department::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->hasBackOfficeAccess(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:departments,code'],
            'parent_department_id' => ['nullable', 'exists:departments,id'],
        ]);

        $department = Department::create($data + ['is_active' => true]);

        return response()->json(['data' => $department], 201);
    }

    public function update(Request $request, Department $department)
    {
        abort_unless($request->user()->hasBackOfficeAccess(), 403);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'parent_department_id' => ['nullable', 'exists:departments,id'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $department->update($data);

        return response()->json(['data' => $department]);
    }
}
