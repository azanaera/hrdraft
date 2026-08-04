<?php

namespace App\Domain\ATS\Http\Controllers;

use App\Domain\ATS\Models\PipelineStage;
use App\Http\Controllers\Controller;

class PipelineStageController extends Controller
{
    public function index()
    {
        return response()->json(['data' => PipelineStage::orderBy('order')->get()]);
    }
}
