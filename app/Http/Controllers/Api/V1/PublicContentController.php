<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Sponsor;
use App\Models\Discount;
use App\Models\Ad;
use App\Models\News;
use App\Http\Resources\V1\SponsorResource;
use App\Http\Resources\V1\DiscountResource;
use App\Http\Resources\V1\AdResource;
use App\Http\Resources\V1\NewsResource;
use Illuminate\Http\Request;

class PublicContentController extends Controller
{
    /**
     * Check if the current authenticated user is an internal role (should not be counted as a visitor).
     */
    private function isInternalUser(): bool
    {
        $user = auth('sanctum')->user();
        if (!$user) return false;
        return in_array($user->role, ['ADMIN', 'OWNER', 'AGENT']);
    }

    /**
     * Record a site visit — skips internal users (ADMIN, OWNER, AGENT).
     */
    private function recordVisit(): void
    {
        if ($this->isInternalUser()) return;

        try {
            $userId = auth('sanctum')->id();
            $ipAddress = request()->ip();
            $visitDate = now()->toDateString();

            // Check if a visit already exists for this day/IP or day/User
            $query = \App\Models\SiteVisit::where('visit_date', $visitDate);
            
            if ($userId) {
                $query->where('user_id', $userId);
            } else {
                $query->where('ip_address', $ipAddress)->whereNull('user_id');
            }

            if (!$query->exists()) {
                \App\Models\SiteVisit::create([
                    'user_id'    => $userId,
                    'ip_address' => $ipAddress,
                    'visit_date' => $visitDate,
                ]);
            }
        } catch (\Exception $e) { /* silent fail */ }
    }

    /**
     * Get all public landing page data in one request for performance
     */
    public function getLandingPageData()
    {
        $sponsors = Sponsor::where('is_active', true)->orderBy('sort_order')->get();
        $discounts = Discount::where('is_active', true)
            ->with('sponsor')
            ->where(function ($q) {
                $q->whereNull('expiry_date')->orWhere('expiry_date', '>=', now()->toDateString());
            })->get();
        $ads = Ad::where('is_active', true)->where(function ($q) {
            $q->whereNull('start_date')->orWhere('start_date', '<=', now()->toDateString());
        })->where(function ($q) {
            $q->whereNull('end_date')->orWhere('end_date', '>=', now()->toDateString());
        })->get();
        $news = News::where('is_active', true)->latest('published_at')->limit(6)->get();

        // Track Visit — internal roles (ADMIN/OWNER/AGENT) are excluded via recordVisit()
        $this->recordVisit();

        return response()->json([
            'sponsors' => SponsorResource::collection($sponsors),
            'discounts' => DiscountResource::collection($discounts),
            'ads' => AdResource::collection($ads),
            'news' => NewsResource::collection($news)
        ]);
    }

    public function getSponsors()
    {
        $sponsors = Sponsor::where('is_active', true)->orderBy('sort_order')->get();
        return SponsorResource::collection($sponsors);
    }

    public function getSponsorDetails(Sponsor $sponsor)
    {
        if (!$sponsor->is_active) abort(404);
        
        $sponsor->load(['images', 'discounts' => function($q) {
            $q->where('is_active', true)->where(function ($q2) {
                $q2->whereNull('expiry_date')->orWhere('expiry_date', '>=', now()->toDateString());
            });
        }]);

        return new SponsorResource($sponsor);
    }

    public function getAllNews()
    {
        $news = News::where('is_active', true)->latest('published_at')->paginate(12);
        return NewsResource::collection($news);
    }

    /**
     * Track a site visit from any page (not just landing page).
     * Internal roles (ADMIN, OWNER, AGENT) are automatically excluded.
     */
    public function trackVisit()
    {
        $this->recordVisit();
        return response()->json(['tracked' => !$this->isInternalUser()]);
    }

    public function getNewsDetails(News $news)
    {
        if (!$news->is_active)
            abort(404);
        return new NewsResource($news);
    }
}
