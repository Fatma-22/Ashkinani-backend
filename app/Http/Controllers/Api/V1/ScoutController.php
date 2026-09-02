<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\User;
use App\Models\Player;
use App\Http\Resources\V1\PlayerResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScoutController extends Controller
{
    public function nonScouts(): JsonResponse
    {
        $admins = Admin::with('user')
            ->where('is_scout', false)
            ->get();
        return $this->success($admins);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'phone' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => bcrypt($validated['password']),
                'role' => 'ADMIN',
                'is_active' => true,
                'phone' => $validated['phone'] ?? null,
            ]);

            $defaultPermissions = [
                'canAddPlayers' => true,
                'canEditPlayers' => true,
                'canDeletePlayers' => true,
                'canAddAgents' => false,
                'canEditAgents' => false,
                'canDeleteAgents' => false,
                'canViewReports' => false,
                'canViewFinancials' => false,
                'canAddDeals' => false,
                'canEditDeals' => false,
                'canDeleteDeals' => false,
                'canManageNews' => false,
                'canManageLanding' => false,
                'canManageCVRequests' => false,
                'canManageMeetings' => false,
                'canManageMembers' => false,
                'canManageSponsors' => false,
                'canManageNutrition' => false,
                'canManageFederations' => false,
                'canManageClubs' => false,
                'canManageScouts' => false,
            ];

            $admin = Admin::create([
                'user_id' => $user->id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'permissions' => $defaultPermissions,
                'is_active' => true,
                'is_scout' => true,
                'created_by' => auth()->id(),
            ]);

            return $this->success($admin->load('user'), 'Scout created successfully', 201);
        });
    }

    public function toggleStatus(Admin $admin, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'is_scout' => 'required|boolean',
        ]);

        $admin->update(['is_scout' => $validated['is_scout']]);

        return $this->success($admin, 'Scout status updated');
    }
    /**
     * Get all scouts (admins with is_scout=true) with their scouted players count.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Admin::with('user')
            ->withCount('scoutedPlayers')
            ->where('is_scout', true);

        // Search by name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $scouts = $query->orderBy('name')->get();

        return $this->success($scouts);
    }

    /**
     * Get a specific scout with their scouted players.
     */
    public function show(Request $request, Admin $admin): JsonResponse
    {
        if (!$admin->is_scout) {
            return $this->error('This admin is not a scout', 404);
        }

        $admin->load('user');
        $admin->loadCount('scoutedPlayers');

        // Get scouted players with pagination
        $playersQuery = Player::with(['mainPhoto', 'agent'])
            ->where('scout_id', $admin->id);

        // Search players
        if ($request->filled('search')) {
            $search = $request->search;
            $playersQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('name_ar', 'like', "%{$search}%");
            });
        }

        $playersQuery->orderBy('created_at', 'desc');

        $players = $playersQuery->paginate($request->get('per_page', 10));

        return $this->success([
            'scout' => $admin,
            'players' => PlayerResource::collection($players)->response()->getData(true),
        ]);
    }

    /**
     * Assign existing players to a specific scout.
     */
    public function assignPlayers(\App\Http\Requests\Scout\AssignPlayersRequest $request, Admin $admin): JsonResponse
    {
        if (!$admin->is_scout) {
            return $this->error('This admin is not a scout', 400);
        }

        Player::whereIn('id', $request->player_ids)
            ->update(['scout_id' => $admin->id]);

        return $this->success(null, 'Players assigned successfully to scout');
    }
}
