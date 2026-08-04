<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Employee\Models\Location;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->hasBackOfficeAccess(), 403);

        return response()->json(['data' => Location::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->hasBackOfficeAccess(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:locations,code'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'size:2'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'minimum_wage' => ['nullable', 'numeric', 'min:0'],
        ]);

        $location = Location::create($data + ['country' => $data['country'] ?? 'US', 'is_active' => true]);

        return response()->json(['data' => $location], 201);
    }

    public function update(Request $request, Location $location)
    {
        abort_unless($request->user()->hasBackOfficeAccess(), 403);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'minimum_wage' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $location->update($data);

        return response()->json(['data' => $location]);
    }
}
