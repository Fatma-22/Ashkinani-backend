<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Deal;
use Illuminate\Http\Request;
use App\Http\Resources\V1\DealResource;
use App\Http\Requests\Deal\StoreDealRequest;
use App\Http\Requests\Deal\UpdateDealRequest;
use Illuminate\Http\JsonResponse;
use App\Services\ArabicSearchService;

class DealController extends Controller
{
    protected $arabicSearchService;

    public function __construct(ArabicSearchService $arabicSearchService)
    {
        $this->arabicSearchService = $arabicSearchService;
    }
    /**
     * Public deals listing — no authentication required.
     * Returns the same data as index() but accessible without a token.
     */
    public function publicIndex(Request $request)
    {
        $query = Deal::with('player');

        if ($request->filled('year')) {
            $query->whereYear('deal_date', $request->year);
        }

        if ($request->filled('from_club')) {
            $fromClub = $request->from_club;
            $query->where(function ($q) use ($fromClub) {
                $q->where('from_club', $fromClub)
                  ->orWhere('from_club_ar', $fromClub);
            });
        }

        if ($request->filled('to_club')) {
            $toClub = $request->to_club;
            $query->where(function ($q) use ($toClub) {
                $q->where('to_club', $toClub)
                  ->orWhere('to_club_ar', $toClub);
            });
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $words = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);
            $likePattern = implode('%', $words) . '%';

            $query->where(function ($q) use ($search, $likePattern) {
                $q->where('from_club', 'like', "{$search}%")
                    ->orWhere('to_club', 'like', "{$search}%")
                    ->orWhere('manual_player_name', 'like', $likePattern)
                    ->orWhereHas('player', function ($pq) use ($likePattern) {
                        $pq->where('name', 'like', $likePattern);
                    });
            });
        }

        $deals = $query->latest('deal_date')->paginate($request->get('per_page', 15));

        return $this->success(DealResource::collection($deals)->response()->getData(true));
    }

    public function stats(): JsonResponse
    {
        // Get all deals to ensure we bypass any driver-specific SQL issues with dates
        $deals = Deal::all();
        $thisYear = (int) now()->year;
        
        $yearlyStats = [];
        $totalDeals = 0;

        foreach ($deals as $deal) {
            if (!$deal->deal_date) continue;
            
            $totalDeals++;
            $year = null;
            
            if ($deal->deal_date instanceof \DateTimeInterface) {
                $year = (int) \Illuminate\Support\Carbon::parse($deal->deal_date)->year;
            } else {
                try {
                    $year = (int) \Illuminate\Support\Carbon::parse($deal->deal_date)->year;
                } catch (\Exception $e) {
                    continue;
                }
            }

            if ($year) {
                if (!isset($yearlyStats[$year])) {
                    $yearlyStats[$year] = 0;
                }
                $yearlyStats[$year]++;
            }
        }

        // Prepare breakdown sorted by year descending
        $breakdown = [];
        foreach ($yearlyStats as $year => $count) {
            $breakdown[] = [
                'year' => $year,
                'count' => $count
            ];
        }
        
        usort($breakdown, function($a, $b) {
            return $b['year'] - $a['year'];
        });

        $thisYearCount = $yearlyStats[$thisYear] ?? 0;
        $lastYearCount = $yearlyStats[$thisYear - 1] ?? 0;

        return $this->success([
            'thisYearCount' => $thisYearCount,
            'lastYearCount' => $lastYearCount,
            'thisYear' => $thisYear,
            'lastYear' => $thisYear - 1,
            'trend' => $thisYearCount >= $lastYearCount ? 'up' : 'down',
            'percentage' => $lastYearCount > 0 ? round((($thisYearCount - $lastYearCount) / $lastYearCount) * 100, 1) : 0,
            'yearlyBreakdown' => $breakdown,
            'total' => $totalDeals
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Deal::with('player');

        // ... existing filters ...
        if ($request->filled('year')) {
            $query->whereYear('deal_date', $request->year);
        }

        if ($request->filled('from_club')) {
            $fromClub = $request->from_club;
            $query->where(function ($q) use ($fromClub) {
                $q->where('from_club', $fromClub)
                  ->orWhere('from_club_ar', $fromClub);
            });
        }

        if ($request->filled('to_club')) {
            $toClub = $request->to_club;
            $query->where(function ($q) use ($toClub) {
                $q->where('to_club', $toClub)
                  ->orWhere('to_club_ar', $toClub);
            });
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('deal_date', [$request->start_date, $request->end_date]);
        }

        if ($request->filled('player_id')) {
            $query->where('player_id', $request->player_id);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $words = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);
            
            // English prefix-start ordered pattern
            $likePattern = implode('%', $words) . '%';
            
            // Arabic prefix-start ordered REGEXP pattern
            $arabicPatterns = array_map(fn($w) => $this->arabicSearchService->generateRegexPattern($w), $words);
            $regexpPattern = '^' . implode('.*', $arabicPatterns);

            $query->where(function ($q) use ($search, $likePattern, $regexpPattern) {
                $q->where('from_club', 'like', "{$search}%")
                    ->orWhere('to_club', 'like', "{$search}%")
                    ->orWhere('manual_player_name', 'like', $likePattern)
                    ->orWhere('manual_player_name_ar', 'REGEXP', $regexpPattern)
                    ->orWhere('manual_player_role', 'like', "{$search}%")
                    ->orWhere('manual_player_sport', 'like', "{$search}%")
                    ->orWhereHas('player', function ($pq) use ($likePattern, $regexpPattern) {
                        $pq->where('name', 'like', $likePattern)
                            ->orWhere('name_ar', 'REGEXP', $regexpPattern);
                    });
            });
        }

        $deals = $query->latest('deal_date')->paginate($request->get('per_page', 15));

        return $this->success(DealResource::collection($deals)->response()->getData(true));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDealRequest $request)
    {
        try {
            $validated = $request->validated();

            $dealData = [
                'player_id' => $validated['playerId'] ?? null,
                'manual_player_name' => $validated['manualPlayerName'] ?? null,
                'manual_player_name_ar' => $validated['manualPlayerNameAr'] ?? null,
                'manual_player_role' => $validated['manualPlayerRole'] ?? null,
                'manual_player_sport' => $validated['manualPlayerSport'] ?? null,
                'from_club' => $validated['fromClub'] ?? null,
                'from_club_ar' => $validated['fromClubAr'] ?? null,
                'to_club' => $validated['toClub'] ?? null,
                'to_club_ar' => $validated['toClubAr'] ?? null,
                'deal_date' => $validated['dealDate'] ?? null,
                'contract_start_date' => $validated['contractStartDate'] ?? null,
                'contract_end_date' => $validated['contractEndDate'] ?? null,
                'contract_url' => $validated['contractUrl'] ?? null,
                'amount' => $validated['amount'] ?? null,
                'currency' => $validated['currency'] ?? 'USD',
                'type' => $validated['type'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ];

            $deal = Deal::create($dealData);

            $this->syncPlayerContract($deal);

            return response()->json([
                'message' => 'Deal created successfully',
                'data' => new DealResource($deal->load('player'))
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Deal store error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to create deal',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Deal $deal)
    {
        return new DealResource($deal->load('player'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDealRequest $request, Deal $deal)
    {
        $validated = $request->validated();

        $dealData = [];
        if (array_key_exists('playerId', $validated))
            $dealData['player_id'] = $validated['playerId'];
        if (array_key_exists('manualPlayerName', $validated))
            $dealData['manual_player_name'] = $validated['manualPlayerName'];
        if (array_key_exists('manualPlayerNameAr', $validated))
            $dealData['manual_player_name_ar'] = $validated['manualPlayerNameAr'];
        if (array_key_exists('manualPlayerRole', $validated))
            $dealData['manual_player_role'] = $validated['manualPlayerRole'];
        if (array_key_exists('manualPlayerSport', $validated))
            $dealData['manual_player_sport'] = $validated['manualPlayerSport'];
        if (array_key_exists('fromClub', $validated))
            $dealData['from_club'] = $validated['fromClub'];
        if (array_key_exists('fromClubAr', $validated))
            $dealData['from_club_ar'] = $validated['fromClubAr'];
        if (array_key_exists('toClub', $validated))
            $dealData['to_club'] = $validated['toClub'];
        if (array_key_exists('toClubAr', $validated))
            $dealData['to_club_ar'] = $validated['toClubAr'];
        if (array_key_exists('dealDate', $validated))
            $dealData['deal_date'] = $validated['dealDate'];
        if (array_key_exists('contractStartDate', $validated))
            $dealData['contract_start_date'] = $validated['contractStartDate'];
        if (array_key_exists('contractEndDate', $validated))
            $dealData['contract_end_date'] = $validated['contractEndDate'];
        if (array_key_exists('contractUrl', $validated))
            $dealData['contract_url'] = $validated['contractUrl'];
        if (array_key_exists('amount', $validated))
            $dealData['amount'] = $validated['amount'];
        if (array_key_exists('currency', $validated))
            $dealData['currency'] = $validated['currency'];
        if (array_key_exists('type', $validated))
            $dealData['type'] = $validated['type'];
        if (array_key_exists('notes', $validated))
            $dealData['notes'] = $validated['notes'];

        $deal->update($dealData);

        $this->syncPlayerContract($deal);

        return response()->json([
            'message' => 'Deal updated successfully',
            'data' => new DealResource($deal->load('player'))
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Deal $deal)
    {
        $deal->delete();

        return response()->json([
            'message' => 'Deal deleted successfully'
        ]);
    }

    /**
     * Get unique list of from_clubs and to_clubs from existing deals.
     */
    public function uniqueClubs(): JsonResponse
    {
        $fromClubs = Deal::select('from_club', 'from_club_ar')
            ->where(function ($q) {
                $q->whereNotNull('from_club')
                  ->orWhereNotNull('from_club_ar');
            })
            ->distinct()
            ->get()
            ->map(function ($deal) {
                return [
                    'name' => $deal->from_club,
                    'name_ar' => $deal->from_club_ar ?: $deal->from_club,
                ];
            })
            ->filter(function ($item) {
                return !empty($item['name']) || !empty($item['name_ar']);
            })
            ->values();

        $toClubs = Deal::select('to_club', 'to_club_ar')
            ->where(function ($q) {
                $q->whereNotNull('to_club')
                  ->orWhereNotNull('to_club_ar');
            })
            ->distinct()
            ->get()
            ->map(function ($deal) {
                return [
                    'name' => $deal->to_club,
                    'name_ar' => $deal->to_club_ar ?: $deal->to_club,
                ];
            })
            ->filter(function ($item) {
                return !empty($item['name']) || !empty($item['name_ar']);
            })
            ->values();

        return $this->success([
            'from_clubs' => $fromClubs,
            'to_clubs' => $toClubs,
        ]);
    }

    /**
     * Sync deal information to the player profile.
     */
    private function syncPlayerContract($deal)
    {
        if (!$deal->player_id) {
            return;
        }

        try {
            $player = \App\Models\Player::find($deal->player_id);
            if ($player) {
                \Log::info("Syncing deal #{$deal->id} to player #{$player->id}");
                
                // 1. Update main player table fields (for general profile view)
                $player->cv_url = $deal->contract_url;
                $player->contract_start_date = $deal->contract_start_date;
                $player->contract_end_date = $deal->contract_end_date;
                
                // 2. Update current club to match the deal's "to_club"
                if ($deal->to_club) {
                    $player->club_name_legacy = $deal->to_club;
                    if ($deal->to_club_ar) {
                        $player->club_name_ar_legacy = $deal->to_club_ar;
                    }
                }

                $player->save();
                
                // 3. Create or update a ClubContract record (for "Club Contracts" tab)
                // We now use deal_id as the primary link to allow automatic deletion
                $clubContract = \App\Models\ClubContract::updateOrCreate(
                    [
                        'deal_id' => $deal->id,
                    ],
                    [
                        'player_id' => $player->id,
                        'club_name' => $deal->to_club ?: 'Private Contract',
                        'club_name_ar' => $deal->to_club_ar,
                        'start_date' => $deal->contract_start_date,
                        'end_date' => $deal->contract_end_date,
                        'file_url' => $deal->contract_url,
                        'notes' => 'Generated automatically from Deal #' . $deal->id . '. ' . ($deal->notes ?: ''),
                    ]
                );

                \Log::info("Successfully synced ClubContract #{$clubContract->id} for player #{$player->id} from deal #{$deal->id}");
            }
        } catch (\Exception $e) {
            \Log::error("Failed to sync player contract for deal #{$deal->id}: " . $e->getMessage());
        }
    }
}
