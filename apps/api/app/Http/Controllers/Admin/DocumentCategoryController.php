<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Documents\Models\DocumentCategory;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DocumentCategoryController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->hasBackOfficeAccess(), 403);

        return response()->json(['data' => DocumentCategory::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->hasBackOfficeAccess(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'requires_signature' => ['boolean'],
            'applicable_to' => ['required', 'in:employee,candidate,all'],
        ]);

        $category = DocumentCategory::create($data);

        return response()->json(['data' => $category], 201);
    }

    public function update(Request $request, DocumentCategory $documentCategory)
    {
        abort_unless($request->user()->hasBackOfficeAccess(), 403);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'requires_signature' => ['sometimes', 'boolean'],
            'applicable_to' => ['sometimes', 'in:employee,candidate,all'],
        ]);

        $documentCategory->update($data);

        return response()->json(['data' => $documentCategory]);
    }
}
