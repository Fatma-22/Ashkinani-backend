<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use App\Http\Requests\Discount\StoreDiscountRequest;
use App\Http\Requests\Discount\UpdateDiscountRequest;
use App\Http\Resources\V1\DiscountResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;

class DiscountController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return DiscountResource::collection(Discount::with('sponsor')->latest()->get());
    }

    public function store(StoreDiscountRequest $request): DiscountResource
    {
        $validated = $request->validated();

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('discounts', 'public');
            $validated['image_path'] = $path;
        }

        $discount = Discount::create($validated);
        return new DiscountResource($discount);
    }

    public function show(Discount $discount): DiscountResource
    {
        return new DiscountResource($discount);
    }

    public function update(UpdateDiscountRequest $request, Discount $discount): DiscountResource
    {
        $validated = $request->validated();

        if ($request->hasFile('image')) {
            if ($discount->image_path)
                Storage::disk('public')->delete($discount->image_path);
            $path = $request->file('image')->store('discounts', 'public');
            $validated['image_path'] = $path;
        } elseif ($request->boolean('remove_image')) {
            if ($discount->image_path)
                Storage::disk('public')->delete($discount->image_path);
            $validated['image_path'] = null;
        }

        $discount->update($validated);
        return new DiscountResource($discount);
    }

    public function destroy(Discount $discount)
    {
        if ($discount->image_path)
            Storage::disk('public')->delete($discount->image_path);
        $discount->delete();
        return response()->json(null, 204);
    }
}
