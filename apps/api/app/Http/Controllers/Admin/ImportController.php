<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Employee\Services\SpreadsheetImportService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    public function __construct(private readonly SpreadsheetImportService $importService)
    {
    }

    public function preview(Request $request)
    {
        abort_unless($request->user()->hasBackOfficeAccess(), 403);

        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:5120']]);

        $result = $this->importService->preview($request->file('file')->get());

        return response()->json($result);
    }

    public function commit(Request $request)
    {
        abort_unless($request->user()->hasBackOfficeAccess(), 403);

        $data = $request->validate(['rows' => ['required', 'array', 'min:1']]);

        $result = $this->importService->commit($data['rows']);

        return response()->json($result, 201);
    }
}
