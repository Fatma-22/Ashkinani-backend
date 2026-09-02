<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Federation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;

class FederationController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Federation::all());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sport_name' => 'required|string|unique:federations,sport_name',
            'logo' => 'nullable|image|max:2048'
        ]);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('federations/logos', 'public');
            $validated['logo_path'] = $path;
        }

        $federation = Federation::create($validated);
        return response()->json($federation, 201);
    }

    public function show(Federation $federation): JsonResponse
    {
        return response()->json($federation);
    }

    public function update(Request $request, Federation $federation): JsonResponse
    {
        $validated = $request->validate([
            'sport_name' => 'required|string|unique:federations,sport_name,' . $federation->id,
            'logo' => 'nullable|image|max:2048'
        ]);

        if ($request->hasFile('logo')) {
            if ($federation->logo_path) {
                Storage::disk('public')->delete($federation->logo_path);
            }
            $path = $request->file('logo')->store('federations/logos', 'public');
            $validated['logo_path'] = $path;
        } elseif ($request->boolean('remove_logo')) {
            if ($federation->logo_path) {
                Storage::disk('public')->delete($federation->logo_path);
            }
            $validated['logo_path'] = null;
        }

        $federation->update($validated);
        return response()->json($federation);
    }

    public function destroy(Federation $federation): JsonResponse
    {
        if ($federation->logo_path) {
            Storage::disk('public')->delete($federation->logo_path);
        }
        $federation->delete();
        return response()->json(null, 204);
    }
}
