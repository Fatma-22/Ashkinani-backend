<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Meeting\StoreMeetingRequest;
use App\Http\Requests\Meeting\UpdateMeetingRequest;
use App\Http\Resources\V1\MeetingResource;
use App\Models\Meeting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\ArabicSearchService;

class MeetingController extends Controller
{
    protected $arabicSearchService;

    public function __construct(ArabicSearchService $arabicSearchService)
    {
        $this->arabicSearchService = $arabicSearchService;
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);
        
        $query = Meeting::with(['player', 'creator'])->orderBy('meeting_date', 'desc')->orderBy('meeting_time', 'desc');

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $words = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);
            
            $likePattern = implode('%', $words) . '%';
            $arabicPatterns = array_map(fn($w) => $this->arabicSearchService->generateRegexPattern($w), $words);
            $regexpPattern = '^' . implode('.*', $arabicPatterns);

            $query->where(function($q) use ($likePattern, $regexpPattern) {
                $q->whereHas('player', function ($pq) use ($likePattern, $regexpPattern) {
                    $pq->where('name', 'like', $likePattern)
                        ->orWhere('name_ar', 'REGEXP', $regexpPattern);
                })
                ->orWhere('related_person_name', 'like', $likePattern)
                ->orWhere('meeting_type', 'like', $likePattern);
            });
        }

        if ($request->filled('day_of_week')) {
            $query->whereRaw('DAYOFWEEK(meeting_date) = ?', [$request->day_of_week]);
        }

        if ($request->filled('month')) {
            $query->whereMonth('meeting_date', $request->month);
        }

        if ($request->filled('year')) {
            $query->whereYear('meeting_date', $request->year);
        }

        if ($perPage === 10000 || $request->has('all')) {
            return MeetingResource::collection($query->get());
        }

        return MeetingResource::collection($query->paginate($perPage));
    }

    public function store(StoreMeetingRequest $request)
    {
        $validated = $request->validated();

        $validated['created_by'] = Auth::id();
        $validated['status'] = $validated['status'] ?? 'SCHEDULED';

        $meeting = Meeting::create($validated);

        return new MeetingResource($meeting->load(['player', 'creator']));
    }

    public function show($id)
    {
        $meeting = Meeting::with(['player', 'creator'])->findOrFail($id);
        return new MeetingResource($meeting);
    }

    public function update(UpdateMeetingRequest $request, $id)
    {
        $meeting = Meeting::findOrFail($id);

        $validated = $request->validated();

        $meeting->update($validated);

        return new MeetingResource($meeting->load(['player', 'creator']));
    }

    public function destroy($id)
    {
        $meeting = Meeting::findOrFail($id);
        $meeting->delete();

        return response()->json(['message' => 'Meeting deleted successfully']);
    }
}
