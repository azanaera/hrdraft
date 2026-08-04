<?php

namespace App\Domain\Documents\Http\Controllers;

use App\Domain\Documents\Models\DocumentCategory;
use App\Http\Controllers\Controller;

class DocumentCategoryController extends Controller
{
    public function index()
    {
        return response()->json(['data' => DocumentCategory::all()]);
    }
}
