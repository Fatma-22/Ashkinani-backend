<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Http\Requests\Ad\StoreAdRequest;
use App\Http\Requests\Ad\UpdateAdRequest;
use App\Http\Resources\V1\AdResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;

class AdController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return AdResource::collection(Ad::latest()->get());
    }

    public function store(StoreAdRequest $request): AdResource
    {
        $validated = $request->validated();

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('ads', 'public');
            $validated['image_path'] = $path;
        }

        $ad = Ad::create($validated);
        return new AdResource($ad);
    }

    public function show(Ad $ad): AdResource
    {
        return new AdResource($ad);
    }

    public function update(UpdateAdRequest $request, Ad $ad): AdResource
    {
        $validated = $request->validated();

        if ($request->hasFile('image')) {
            if ($ad->image_path)
                Storage::disk('public')->delete($ad->image_path);
            $path = $request->file('image')->store('ads', 'public');
            $validated['image_path'] = $path;
        }

        $ad->update($validated);
        return new AdResource($ad);
    }

    public function destroy(Ad $ad)
    {
        if ($ad->image_path)
            Storage::disk('public')->delete($ad->image_path);
        $ad->delete();
        return response()->json(null, 204);
    }
}
