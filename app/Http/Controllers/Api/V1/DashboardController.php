<?php
/** @noinspection PhpHierarchyChecksInspection */

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Player;
use App\Models\Deal;
use App\Models\User;
use App\Models\SiteVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    private function applyRoleFilter($query)
    {
        $user = Auth::guard('sanctum')->user();
        if ($user && $user->role === 'AGENT') {
            $agent = $user->agent;
            if ($agent) {
                $query->where('agent_id', $agent->id);
            } else {
                // If agent profile missing, return no players
                $query->whereRaw('1 = 0');
            }
        }
        return $query;
    }

    private function applyDateFilter($query, Request $request)
    {
        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('contract_end_date', '>=', $request->start_date);
        }
        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('contract_end_date', '<=', $request->end_date);
        }
        return $query;
    }

    public function getStats(Request $request)
    {
        $sport = $request->query('sport');
        $start_date = $request->query('start_date');
        $end_date = $request->query('end_date');

        $query = Player::query();
        $this->applyRoleFilter($query);

        // Save base query for KPIs that shouldn't be over-filtered by specific expiry ranges
        $kpiQuery = clone $query;

        // Apply additional filters for reports/charts specifically
        $this->applyDateFilter($query, $request);

        if ($sport && $sport !== 'All') {
            $query->where('sport', $sport);
            $kpiQuery->where('sport', $sport);
        }

        // Total Players
        $totalPlayers = (clone $kpiQuery)->count();

        // Active Contracts (from Player model)
        // Counts as active if:
        // - main column: end date is null or in the future, and not terminated
        // - OR player has an active Ashkanani agency contract
        $now = Carbon::now()->toDateString();
        $activeContracts = (clone $kpiQuery)
            ->where(function ($q) use ($now) {
                $q->where(function ($mq) use ($now) {
                    $mq->where(function ($sq) use ($now) {
                        $sq->whereNull('contract_end_date')
                            ->orWhere('contract_end_date', '>=', $now);
                    })
                    ->where(function ($sq) {
                        $sq->whereNull('contract_nature')
                          ->orWhere('contract_nature', '!=', 'TERMINATION');
                    });
                })
                ->orWhereHas('documents', function ($c) use ($now) {
                    $c->where('type', 'contract')
                      ->whereNotNull('end_date')
                      ->whereDate('end_date', '>=', $now);
                });
            })
            ->count();

        // Expiring Soon (from Player model, define as 2 months)
        $expiringSoon = (clone $kpiQuery)
            ->whereNotNull('contract_end_date')
            ->where('contract_end_date', '<=', Carbon::now()->addMonths(2))
            ->where('contract_end_date', '>=', $now)
            ->count();

        // Total Market Value (Sum of market_value only as requested)
        $totalMarketValue = (clone $kpiQuery)->sum('market_value');

        // Deal Stats
        $dealQuery = Deal::query();

        // Filter deals by agent if user is an agent
        $user = Auth::guard('sanctum')->user();
        if ($user && $user->role === 'AGENT') {
            $agent = $user->agent;
            if ($agent) {
                $dealQuery->whereHas('player', function ($q) use ($agent) {
                    $q->where('agent_id', $agent->id);
                });
            } else {
                $dealQuery->whereRaw('1 = 0');
            }
        }

        if ($sport && $sport !== 'All') {
            $dealQuery->where(function ($q) use ($sport) {
                $q->whereHas('player', function ($pq) use ($sport) {
                    $pq->where('sport', $sport);
                });
                // Manual deals don't have sport, so they are excluded when a specific sport is selected
                // unless we want to include them? Usually no, because we filter by sport to see specific data.
            });
        }

        // Apply Date Filter to deals
        if ($start_date) {
            $dealQuery->whereDate('deal_date', '>=', $start_date);
        }
        if ($end_date) {
            $dealQuery->whereDate('deal_date', '<=', $end_date);
        }

        $totalDeals = (clone $dealQuery)->count();
        $dealsThisMonth = (clone $dealQuery)
            ->whereYear('deal_date', Carbon::now()->year)
            ->whereMonth('deal_date', Carbon::now()->month)
            ->count();
        $totalDealsAmount = (clone $dealQuery)->sum('amount');

        // Strategic Indicators
        $now = Carbon::now();
        $currentYear = $now->year;
        $youthPlayersCount = (clone $query)->where('date_of_birth', '>=', $currentYear - 23)->count();
        $proPlayersCount = $totalPlayers - $youthPlayersCount;

        // Contract Stability (Average remaining months of contracts that haven't expired)
        $activeContractsQuery = (clone $query)
            ->whereNotNull('contract_end_date')
            ->whereDate('contract_end_date', '>=', Carbon::now()->toDateString());

        $contractStability = 0; // Default placeholder

        try {
            $contracts = (clone $activeContractsQuery)->select('contract_end_date')->limit(100)->get();
            if ($contracts->count() > 0) {
                $totalMonths = 0;
                foreach ($contracts as $c) {
                    $end = Carbon::parse($c->contract_end_date);
                    $totalMonths += max(0, $now->diffInMonths($end));
                }
                $avgMonths = $totalMonths / $contracts->count();
                // User requested to calculate based on 1 year (12 months) instead of 36
                $contractStability = min(100, ($avgMonths / 12) * 100);
            }
        } catch (\Exception $e) {
            // Fallback
        }

        // Agency Concentration (Top 3 agents share)
        $topAgentsCount = (clone $query)
            ->whereNotNull('agent_id')
            ->select('agent_id', DB::raw('count(*) as count'))
            ->groupBy('agent_id')
            ->orderByDesc('count')
            ->limit(3)
            ->get()
            ->sum('count');
        $topAgentConcentration = $totalPlayers > 0 ? ($topAgentsCount / $totalPlayers) * 100 : 0;

        // Contract Nature Stats (Distinguish between full affiliates and simple profiles)
        $signingCount = (clone $kpiQuery)->where('contract_nature', 'SIGNING')->count();
        $authorizationCount = (clone $kpiQuery)->where('contract_nature', 'AUTHORIZATION')->count();
        $notJoinedCount = (clone $kpiQuery)->where('contract_nature', 'NOT_JOINED')->count();
        $terminationCount = (clone $kpiQuery)->where('contract_nature', 'TERMINATION')->count();

        return $this->success([
            'totalPlayers' => $totalPlayers,
            'activeContracts' => $activeContracts,
            'expiringSoon' => $expiringSoon,
            'totalMarketValue' => $totalMarketValue,
            'totalDeals' => $totalDeals,
            'dealsThisMonth' => $dealsThisMonth,
            'totalDealsAmount' => $totalDealsAmount,
            'youthPlayersCount' => $youthPlayersCount,
            'proPlayersCount' => $proPlayersCount,
            'contractStability' => (float) $contractStability,
            'topAgentConcentration' => $topAgentConcentration,
            'signingCount' => $signingCount,
            'authorizationCount' => $authorizationCount,
            'notJoinedCount' => $notJoinedCount,
            'terminationCount' => $terminationCount,
            
            // Membership Stats
            'totalMembers' => User::where('role', 'PUBLIC')->count(),
            'playersMemberCount' => User::where('role', 'PUBLIC')->where('member_type', 'PLAYER')->count(),
            'coachesMemberCount' => User::where('role', 'PUBLIC')->where('member_type', 'COACH')->count(),
            'scoutsMemberCount' => User::where('role', 'PUBLIC')->where('member_type', 'SCOUT')->count(),
            'clubsMemberCount' => User::where('role', 'PUBLIC')->where('member_type', 'CLUB')->count(),
            'administratorsMemberCount' => User::where('role', 'PUBLIC')->where('member_type', 'ADMINISTRATOR')->count(),
            'refereesMemberCount' => User::where('role', 'PUBLIC')->where('member_type', 'REFEREE')->count(),
            'photographersMemberCount' => User::where('role', 'PUBLIC')->where('member_type', 'PHOTOGRAPHER')->count(),
            'othersMemberCount' => User::where('role', 'PUBLIC')->where('member_type', 'OTHER')->count(),

            // Visit Stats — internal roles (ADMIN/OWNER/AGENT) are excluded
            'guestVisits' => SiteVisit::whereNull('user_id')->count(),
            'registeredVisits' => SiteVisit::whereNotNull('user_id')
                ->whereNotExists(function ($q) {
                    $q->from('users')
                      ->whereColumn('users.id', 'site_visits.user_id')
                      ->whereIn('users.role', ['ADMIN', 'OWNER', 'AGENT']);
                })
                ->count(),

            // Daily activity (last 7 days) — internal roles excluded
            'dailyStats' => collect(range(6, 0))->map(function($i) {
                $date = Carbon::now()->subDays($i)->toDateString();
                return [
                    'date' => $date,
                    'guest' => SiteVisit::whereNull('user_id')->whereDate('visit_date', $date)->count(),
                    'registered' => SiteVisit::whereNotNull('user_id')
                        ->whereNotExists(function ($q) {
                            $q->from('users')
                              ->whereColumn('users.id', 'site_visits.user_id')
                              ->whereIn('users.role', ['ADMIN', 'OWNER', 'AGENT']);
                        })
                        ->whereDate('visit_date', $date)
                        ->count(),
                    'registrations' => User::where('role', 'PUBLIC')->whereDate('created_at', $date)->count(),
                ];
            })->values(),
        ]);
    }

    public function getDealTypeStats(Request $request)
    {
        $sport = $request->query('sport');
        $start_date = $request->query('start_date');
        $end_date = $request->query('end_date');

        $query = Deal::query();

        // Apply role filtering (Agents only see deals for their players)
        $user = Auth::guard('sanctum')->user();
        if ($user && $user->role === 'AGENT') {
            $agent = $user->agent;
            if ($agent) {
                $query->whereHas('player', function ($q) use ($agent) {
                    $q->where('agent_id', $agent->id);
                });
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if ($sport && $sport !== 'All') {
            $query->whereHas('player', function ($q) use ($sport) {
                $q->where('sport', $sport);
            });
        }

        if ($start_date) {
            $query->whereDate('deal_date', '>=', $start_date);
        }
        if ($end_date) {
            $query->whereDate('deal_date', '<=', $end_date);
        }

        $stats = $query->select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->get();

        return $this->success($stats);
    }

    public function getMarketValueDistribution(Request $request)
    {
        $sport = $request->query('sport');
        $query = Player::query();
        $this->applyRoleFilter($query);
        $this->applyDateFilter($query, $request);

        if ($sport && $sport !== 'All') {
            $query->where('sport', $sport);
        }

        $ranges = [
            ['min' => 0, 'max' => 10000000, 'label' => '0-10M'],
            ['min' => 10000000, 'max' => 30000000, 'label' => '10-30M'],
            ['min' => 30000000, 'max' => 50000000, 'label' => '30-50M'],
            ['min' => 50000000, 'max' => 9999999999, 'label' => '50M+'],
        ];

        $distribution = [];
        foreach ($ranges as $range) {
            $count = (clone $query)
                ->where('market_value', '>=', $range['min'])
                ->where('market_value', '<', $range['max'])
                ->count();

            $value = (clone $query)
                ->where('market_value', '>=', $range['min'])
                ->where('market_value', '<', $range['max'])
                ->sum('market_value');

            $distribution[] = [
                'range' => $range['label'],
                'count' => $count,
                'value' => $value,
            ];
        }

        return $this->success($distribution);
    }

    public function getContractStatusData(Request $request)
    {
        $sport = $request->query('sport');
        $query = Player::query();
        $this->applyRoleFilter($query);

        // Distribution of status is typically CURRENT state 
        if ($sport && $sport !== 'All') {
            $query->where('sport', $sport);
        }

        $now = Carbon::now()->toDateString();

        $activeCount = (clone $query)
            ->where(function ($q) use ($now) {
                $q->where(function ($mq) use ($now) {
                    $mq->where(function ($sq) use ($now) {
                        $sq->whereNull('contract_end_date')
                            ->orWhereDate('contract_end_date', '>=', $now);
                    })
                    ->where(function ($sq) {
                        $sq->whereNull('contract_nature')
                          ->orWhere('contract_nature', '!=', 'TERMINATION');
                    });
                })
                ->orWhereHas('documents', function ($c) use ($now) {
                    $c->where('type', 'contract')
                      ->whereNotNull('end_date')
                      ->whereDate('end_date', '>=', $now);
                });
            })
            ->count();

        $expiredCount = (clone $query)
            ->where(function ($q) use ($now) {
                $q->where('contract_nature', 'TERMINATION')
                  ->orWhere(function ($sq) use ($now) {
                      $sq->whereNotNull('contract_end_date')
                         ->whereDate('contract_end_date', '<', $now);
                  });
            })
            ->whereDoesntHave('documents', function ($c) use ($now) {
                $c->where('type', 'contract')
                  ->whereNotNull('end_date')
                  ->whereDate('end_date', '>=', $now);
            })
            ->count();

        $stats = [
            ['status' => 'ACTIVE', 'count' => $activeCount],
            ['status' => 'EXPIRED', 'count' => $expiredCount],
        ];

        return $this->success($stats);
    }

    public function getContractExpiryTimeline(Request $request)
    {
        $sport = $request->query('sport');
        $query = Player::query();
        $this->applyRoleFilter($query);
        $this->applyDateFilter($query, $request);

        if ($sport && $sport !== 'All') {
            $query->where('sport', $sport);
        }

        $timeline = [];
        for ($i = 0; $i < 12; $i++) {
            $date = Carbon::now()->addMonths($i);
            $month = $date->startOfMonth()->format('Y-m-d');

            $count = (clone $query)
                ->whereYear('contract_end_date', $date->year)
                ->whereMonth('contract_end_date', $date->month)
                ->count();

            $timeline[] = [
                'month' => $month,
                'count' => $count,
            ];
        }

        return $this->success($timeline);
    }
}
