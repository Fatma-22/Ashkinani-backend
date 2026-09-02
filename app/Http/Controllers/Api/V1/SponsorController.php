<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Sponsor;
use App\Http\Requests\Sponsor\StoreSponsorRequest;
use App\Http\Requests\Sponsor\UpdateSponsorRequest;
use App\Http\Resources\V1\SponsorResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;

class SponsorController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return SponsorResource::collection(Sponsor::with('images')->orderBy('sort_order')->get());
    }

    public function store(StoreSponsorRequest $request): SponsorResource
    {
        $validated = $request->validated();

        // Debug: Log received files
        \Log::info('Sponsor Store Request', [
            'has_logo' => $request->hasFile('logo'),
            'has_contract' => $request->hasFile('contract'),
            'has_gallery' => $request->hasFile('gallery'),
            'all_files' => $request->allFiles(),
            'validated_keys' => array_keys($validated),
        ]);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('sponsors/logos', 'public');
            $validated['logo_path'] = $path;
            \Log::info('Logo stored at: ' . $path);
        }

        if ($request->hasFile('contract')) {
            $path = $request->file('contract')->store('sponsors/contracts', 'public');
            $validated['contract_path'] = $path;
            \Log::info('Contract stored at: ' . $path);
        }

        $sponsor = Sponsor::create($validated);

        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $image) {
                $path = $image->store('sponsors/gallery', 'public');
                $sponsor->images()->create(['image_path' => $path]);
                \Log::info('Gallery image stored at: ' . $path);
            }
        }

        return new SponsorResource($sponsor->load('images'));
    }

    public function show(Sponsor $sponsor): SponsorResource
    {
        return new SponsorResource($sponsor->load(['images', 'discounts']));
    }

    public function update(UpdateSponsorRequest $request, Sponsor $sponsor): SponsorResource
    {
        $validated = $request->validated();

        if ($request->hasFile('logo')) {
            if ($sponsor->logo_path)
                Storage::disk('public')->delete($sponsor->logo_path);
            $path = $request->file('logo')->store('sponsors/logos', 'public');
            $validated['logo_path'] = $path;
        } elseif ($request->boolean('remove_logo')) {
            if ($sponsor->logo_path)
                Storage::disk('public')->delete($sponsor->logo_path);
            $validated['logo_path'] = null;
        }

        if ($request->hasFile('contract')) {
            if ($sponsor->contract_path)
                Storage::disk('public')->delete($sponsor->contract_path);
            $path = $request->file('contract')->store('sponsors/contracts', 'public');
            $validated['contract_path'] = $path;
        } elseif ($request->boolean('remove_contract')) {
            if ($sponsor->contract_path)
                Storage::disk('public')->delete($sponsor->contract_path);
            $validated['contract_path'] = null;
        }

        $sponsor->update($validated);

        // Handle deleted gallery images
        if ($request->has('deleted_gallery_ids')) {
            $imagesToDelete = $sponsor->images()->whereIn('id', $request->deleted_gallery_ids)->get();
            foreach ($imagesToDelete as $image) {
                Storage::disk('public')->delete($image->image_path);
                $image->delete();
            }
        }

        // Handle new gallery images
        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $image) {
                $path = $image->store('sponsors/gallery', 'public');
                $sponsor->images()->create(['image_path' => $path]);
            }
        }

        return new SponsorResource($sponsor->load('images'));
    }

    public function destroy(Sponsor $sponsor)
    {
        if ($sponsor->logo_path)
            Storage::disk('public')->delete($sponsor->logo_path);
        if ($sponsor->contract_path)
            Storage::disk('public')->delete($sponsor->contract_path);
        
        // Delete gallery images
        foreach ($sponsor->images as $image) {
            Storage::disk('public')->delete($image->image_path);
        }
        $sponsor->images()->delete();

        $sponsor->delete();
        return response()->json(null, 204);
    }
    public function shareSponsor($id)
    {
        // Try finding by ID first, then by name
        $sponsor = Sponsor::with('images')
            ->where('id', $id)
            ->orWhere('name_en', $id)
            ->orWhere('name_ar', $id)
            ->first();

        if (!$sponsor) {
            $frontendDomain = rtrim(env('FRONTEND_URL', 'https://ashkananitransfer.com'), '/');
            return redirect($frontendDomain . '/sponsors');
        }

        $isAr = app()->getLocale() === 'ar';
        $sponsorName = $isAr ? ($sponsor->name_ar ?: $sponsor->name_en) : ($sponsor->name_en ?: $sponsor->name_ar);
        
        $pageTitle = "{$sponsorName} | Ashkanani Sport Sponsor";
        $metaDescription = $isAr 
            ? "تعرف على {$sponsorName}، أحد شركاء وكالة أشكناني سبورت."
            : "Learn more about {$sponsorName}, a partner of Ashkanani Sport.";

        $ogImage = asset('logo2.png');
        if ($sponsor->logo_path) {
            $ogImage = filter_var($sponsor->logo_path, FILTER_VALIDATE_URL) 
                ? $sponsor->logo_path 
                : asset('storage/' . $sponsor->logo_path);
        }

        return view('share-sponsor', [
            'ogTitle' => $pageTitle,
            'ogDescription' => $metaDescription,
            'ogImage' => $ogImage,
            'id' => $sponsor->id
        ]);
    }
}
