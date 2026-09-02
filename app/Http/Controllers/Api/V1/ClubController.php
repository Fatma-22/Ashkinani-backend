<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Club;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;

class ClubController extends Controller
{
    public function index(): JsonResponse
    {
        return $this->success(\App\Http\Resources\V1\ClubResource::collection(Club::all()));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'name_ar' => 'nullable|string',
            'country' => 'nullable|string',
            'logo' => 'nullable|image|max:2048'
        ]);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('clubs/logos', 'public');
            $validated['logo_path'] = $path;
        }

        $club = Club::create($validated);
        return $this->success($club, 'Club created successfully', 201);
    }

    public function show(Club $club): JsonResponse
    {
        $club->load('players');
        return $this->success([
            'club' => new \App\Http\Resources\V1\ClubResource($club),
            'players' => \App\Http\Resources\V1\PlayerResource::collection($club->players)
        ]);
    }

    public function update(Request $request, Club $club): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'name_ar' => 'nullable|string',
            'country' => 'nullable|string',
            'logo' => 'nullable|image|max:2048'
        ]);

        if ($request->hasFile('logo')) {
            if ($club->logo_path) {
                Storage::disk('public')->delete($club->logo_path);
            }
            $path = $request->file('logo')->store('clubs/logos', 'public');
            $validated['logo_path'] = $path;
        } elseif ($request->boolean('remove_logo')) {
            if ($club->logo_path) {
                Storage::disk('public')->delete($club->logo_path);
            }
            $validated['logo_path'] = null;
        }

        $club->update($validated);
        return $this->success($club, 'Club updated successfully');
    }

    public function destroy(Club $club): JsonResponse
    {
        if ($club->logo_path) {
            Storage::disk('public')->delete($club->logo_path);
        }
        $club->delete();
        return $this->success(null, 'Club deleted successfully', 204);
    }
}
