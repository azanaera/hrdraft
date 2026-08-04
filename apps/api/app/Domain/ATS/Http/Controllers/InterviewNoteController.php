<?php

namespace App\Domain\ATS\Http\Controllers;

use App\Domain\ATS\Models\Application;
use App\Domain\ATS\Models\InterviewNote;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class InterviewNoteController extends Controller
{
    public function store(Request $request, Application $application)
    {
        $data = $request->validate([
            'scheduled_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
        ]);

        $note = $application->interviewNotes()->create([
            ...$data,
            'interviewer_user_id' => $request->user()->id,
        ]);

        return response()->json(['data' => $note], 201);
    }
}
