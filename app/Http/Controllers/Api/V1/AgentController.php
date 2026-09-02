<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agent\StoreAgentRequest;
use App\Http\Requests\Agent\UpdateAgentRequest;
use App\Http\Resources\V1\AgentResource;
use App\Models\Agent;
use App\Models\Player;
use App\Models\User;
use Illuminate\Http\JsonResponse;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Services\ArabicSearchService;


class AgentController extends Controller
{
    protected $arabicSearchService;

    public function __construct(ArabicSearchService $arabicSearchService)
    {
        $this->arabicSearchService = $arabicSearchService;
    }
    /**
     * Display a listing of agents.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Agent::query();

        if ($request->filled('search')) {
            $search = trim($request->search);
            $words = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);
            
            // English prefix-start ordered pattern
            $likePattern = implode('%', $words) . '%';
            
            // Arabic prefix-start ordered REGEXP pattern
            $arabicPatterns = array_map(fn($w) => $this->arabicSearchService->generateRegexPattern($w), $words);
            $regexpPattern = '^' . implode('.*', $arabicPatterns);

            $query->where(function ($q) use ($search, $likePattern, $regexpPattern) {
                $q->where('name', 'like', $likePattern)
                    ->orWhere('name_ar', 'REGEXP', $regexpPattern)
                    ->orWhere('company', 'like', $likePattern)
                    ->orWhere('company_ar', 'REGEXP', $regexpPattern)
                    ->orWhere('email', 'like', "{$search}%")
                    ->orWhere('phone', 'like', "{$search}%");
            });
        }

        $perPage = $request->get('per_page', 15);
        if ($perPage == -1) {
            $agents = $query->get();
            return $this->success(['data' => AgentResource::collection($agents)]);
        }

        $agents = $query->orderBy('id', 'desc')->paginate($perPage);

        return $this->success(AgentResource::collection($agents)->response()->getData(true));
    }

    /**
     * Store a newly created agent.
     */
    public function store(StoreAgentRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $data = $request->validated();

            // 1. Create the User account first
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => 'AGENT',
                'is_active' => true,
            ]);

            // 2. Prepare Agent data
            $agentData = $data;
            $agentData['user_id'] = $user->id;
            unset($agentData['password']); // Don't save raw password in agents table if it exists

            if ($request->hasFile('avatar')) {
                $agentData['avatar'] = $request->file('avatar')->store('avatars', 'public');
                $user->update(['avatar' => $agentData['avatar']]);
            }

            // 3. Create the Agent profile
            $agent = Agent::create($agentData);

            // 4. Handle player assignments
            if ($request->has('assigned_player_ids')) {
                Player::whereIn('id', $request->assigned_player_ids)->update(['agent_id' => $agent->id]);
            }

            return $this->success(new AgentResource($agent), 'Agent and User account created successfully', 201);
        });
    }



    /**
     * Display the specified agent.
     */
    public function show(Agent $agent): JsonResponse
    {
        $agent->load(['players.club', 'players.mainPhoto']);

        return $this->success(new AgentResource($agent));
    }

    /**
     * Update the specified agent.
     */
    public function update(UpdateAgentRequest $request, Agent $agent): JsonResponse
    {
        return DB::transaction(function () use ($request, $agent) {
            $data = $request->validated();

            // If agent doesn't have a user account, create one
            if (!$agent->user) {
                $user = User::create([
                    'name' => $data['name'] ?? $agent->name,
                    'email' => $data['email'] ?? $agent->email,
                    'password' => isset($data['password']) ? Hash::make($data['password']) : Hash::make('default_password'),
                    'role' => 'AGENT',
                    'is_active' => true,
                    'avatar' => $agent->avatar
                ]);
                $agent->user_id = $user->id;
                $agent->save();
            } else {
                // Handle password hashing and User account update if provided
                if (isset($data['password']) && !empty($data['password'])) {
                    $hashedPassword = Hash::make($data['password']);
                    $agent->user->update(['password' => $hashedPassword]);
                }

                // Update associated user email/name if they changed
                $userUpdates = [];
                if (isset($data['name']))
                    $userUpdates['name'] = $data['name'];
                if (isset($data['email']))
                    $userUpdates['email'] = $data['email'];
                if (!empty($userUpdates)) {
                    $agent->user->update($userUpdates);
                }
            }

            if ($request->hasFile('avatar')) {
                // Delete old avatar if it exists
                if ($agent->avatar) {
                    Storage::disk('public')->delete($agent->avatar);
                }
                $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
                if ($agent->user) {
                    $agent->user->update(['avatar' => $data['avatar']]);
                }
            }

            $agent->update($data);

            if ($request->has('assigned_player_ids')) {
                // Remove agent from players no longer in the list
                Player::where('agent_id', $agent->id)
                    ->whereNotIn('id', $request->assigned_player_ids)
                    ->update(['agent_id' => null]);

                // Add agent to new players
                Player::whereIn('id', $request->assigned_player_ids)
                    ->update(['agent_id' => $agent->id]);
            }

            return $this->success(new AgentResource($agent), 'Agent updated successfully');
        });
    }



    /**
     * Remove the specified agent.
     */
    public function destroy(Agent $agent): JsonResponse
    {
        return DB::transaction(function () use ($agent) {
            // Explicitly set agent_id to null for all associated players and contracts
            $agent->players()->update(['agent_id' => null]);
            $agent->contracts()->update(['agent_id' => null]);

            // Delete associated user account if it exists
            if ($agent->user) {
                $agent->user->delete();
            }

            $agent->delete();

            return $this->success(null, 'Agent deleted successfully');
        });
    }


}
