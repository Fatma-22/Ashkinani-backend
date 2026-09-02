<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contract\StoreContractRequest;
use App\Http\Requests\Contract\UpdateContractRequest;
use App\Http\Resources\V1\ContractResource;
use App\Models\Contract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\ArabicSearchService;

class ContractController extends Controller
{
    protected $arabicSearchService;

    public function __construct(ArabicSearchService $arabicSearchService)
    {
        $this->arabicSearchService = $arabicSearchService;
    }
    /**
     * Display a listing of contracts.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Contract::with(['player', 'agent']);

        // Agent Isolation
        if (Auth::guard('sanctum')->check() && Auth::guard('sanctum')->user()->role === 'AGENT') {
            $agent = Auth::guard('sanctum')->user()->agent;
            if ($agent) {
                $query->where(function ($q) use ($agent) {
                    $q->where('agent_id', $agent->id)
                        ->orWhereHas('player', function ($pq) use ($agent) {
                            $pq->where('agent_id', $agent->id);
                        });
                });
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $contracts = $query->when($request->has('player_id'), function ($query) use ($request) {
            return $query->where('player_id', $request->get('player_id'));
        })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->get('search'));
                $words = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);
                
                $likePattern = implode('%', $words) . '%';
                $arabicPatterns = array_map(fn($w) => $this->arabicSearchService->generateRegexPattern($w), $words);
                $regexpPattern = '^' . implode('.*', $arabicPatterns);

                return $query->whereHas('player', function ($q) use ($likePattern, $regexpPattern) {
                    $q->where('name', 'like', $likePattern)
                        ->orWhere('name_ar', 'REGEXP', $regexpPattern);
                });
            })
            ->when($request->has('type'), function ($query) use ($request) {
                return $query->where('type', $request->get('type'));
            })
            ->when($request->has('status'), function ($query) use ($request) {
                return $query->where('status', $request->get('status'));
            })
            ->when($request->has('start_date'), function ($query) use ($request) {
                return $query->whereDate('end_date', '>=', $request->get('start_date'));
            })
            ->when($request->has('end_date'), function ($query) use ($request) {
                return $query->whereDate('end_date', '<=', $request->get('end_date'));
            })
            ->paginate($request->get('per_page', 15));

        return $this->success(ContractResource::collection($contracts)->response()->getData(true));
    }

    /**
     * Store a newly created contract.
     */
    public function store(StoreContractRequest $request): JsonResponse
    {
        $contract = Contract::create($request->validated());

        return $this->success(new ContractResource($contract), 'Contract created successfully', 201);
    }

    /**
     * Display the specified contract.
     */
    public function show(Contract $contract): JsonResponse
    {
        $contract->load(['player', 'agent']);

        return $this->success(new ContractResource($contract));
    }

    /**
     * Update the specified contract.
     */
    public function update(UpdateContractRequest $request, Contract $contract): JsonResponse
    {
        $contract->update($request->validated());

        return $this->success(new ContractResource($contract), 'Contract updated successfully');
    }

    /**
     * Remove the specified contract.
     */
    public function destroy(Contract $contract): JsonResponse
    {
        $contract->delete();

        return $this->success(null, 'Contract deleted successfully');
    }
}
