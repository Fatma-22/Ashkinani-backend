<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\PlayerResource;
use App\Models\Player;
use App\Services\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MemberProfileController extends Controller
{
    protected $mediaService;

    public function __construct(MediaService $mediaService)
    {
        $this->mediaService = $mediaService;
    }

    /**
     * Build a full player data array for member access (shows all fields)
     */
    private function buildPlayerData(Player $player): array
    {
        $player->load(['photos', 'mainPhoto', 'agent', 'contracts', 'documents', 'certificates']);

        $mainPhoto = $player->mainPhoto;

        return [
            'id' => $player->id,
            'role' => $player->profile_role,
            'name' => $player->name,
            'nameAr' => $player->name_ar,
            'email' => $player->email,
            'phone' => $player->phone,
            'address' => $player->address,
            'nationalId' => $player->national_id,
            'sport' => $player->sport,
            'nationality' => $player->nationality,
            'nationalityAr' => $player->nationality_ar,
            'designerType' => $player->designer_type,
            'dateOfBirth' => $player->date_of_birth,
            'bornInKuwait' => $player->born_in_kuwait,
            'gender' => $player->gender,
            'legalStatus' => $player->legal_status,
            'positions' => $player->positions,
            'club' => $player->club,
            'club_id' => $player->club_id,
            'national_team_id' => $player->national_team_id,
            'clubAr' => $player->club_ar,
            'clubLogo' => $player->club_logo,
            'jerseyNumber' => $player->jersey_number,
            'preferredFoot' => $player->preferred_foot,
            'dealStatus' => $player->deal_status,
            'marketValue' => $player->market_value,
            'height' => $player->height,
            'weight' => $player->weight,
            'bio' => $player->bio,
            'bioAr' => $player->bio_ar,
            'youtubeUrl' => $player->youtube_url,
            'transfermarktUrl' => $player->transfermarkt_url,
            'instagramUrl' => $player->instagram_url,
            'driveUrl' => $player->drive_url,
            // Volleyball-specific files
            'volleyballStatsPdf' => $player->volleyball_stats_pdf
                ? (filter_var($player->volleyball_stats_pdf, FILTER_VALIDATE_URL)
                    ? $player->volleyball_stats_pdf
                    : asset('storage/' . $player->volleyball_stats_pdf))
                : null,
            'volleyballRankingImage' => $player->volleyball_ranking_image
                ? (filter_var($player->volleyball_ranking_image, FILTER_VALIDATE_URL)
                    ? $player->volleyball_ranking_image
                    : asset('storage/' . $player->volleyball_ranking_image))
                : null,
            // Volleyball-specific jump/reach measurements (cm)
            'volleyballSpikeReach' => $player->volleyball_spike_reach,
            'volleyballBlockReach' => $player->volleyball_block_reach,
            'notes' => $player->notes,
            'notesAr' => $player->notes_ar,
            'previousClubs' => $player->previous_clubs,
            'previousClubsAr' => $player->previous_clubs_ar,
            'achievements' => $player->achievements,
            'achievementsAr' => $player->achievements_ar,
            'stats' => $player->current_stats,
            'cvUrl' => $player->cv_url
                ? (filter_var($player->cv_url, FILTER_VALIDATE_URL)
                    ? $player->cv_url
                    : asset('storage/' . $player->cv_url))
                : null,
            'strategyPdf' => $player->strategy_pdf
                ? (filter_var($player->strategy_pdf, FILTER_VALIDATE_URL)
                    ? $player->strategy_pdf
                    : asset('storage/' . $player->strategy_pdf))
                : null,
            'certificates' => $player->certificates->map(function ($cert) {
                return [
                    'id' => $cert->id,
                    'certificate_name' => $cert->certificate_name,
                    'certificate_type' => $cert->certificate_type,
                    'issuing_body' => $cert->issuing_body,
                    'year_obtained' => $cert->year_obtained,
                    'level' => $cert->level,
                    'certificate_number' => $cert->certificate_number,
                    'source_type' => $cert->source_type,
                    'certificate_file' => $cert->certificate_file 
                        ? (filter_var($cert->certificate_file, FILTER_VALIDATE_URL) 
                            ? $cert->certificate_file 
                            : asset('storage/' . $cert->certificate_file))
                        : null,
                ];
            }),
            'mainPhoto' => $mainPhoto ? [
                'id' => $mainPhoto->id,
                'url' => $mainPhoto->url
                    ? (filter_var($mainPhoto->url, FILTER_VALIDATE_URL)
                        ? $mainPhoto->url
                        : asset('storage/' . $mainPhoto->url))
                    : null,
                'isMain' => true,
            ] : null,
            'photos' => $player->photos->map(function ($photo) {
                return [
                    'id' => $photo->id,
                    'url' => $photo->url
                        ? (filter_var($photo->url, FILTER_VALIDATE_URL)
                            ? $photo->url
                            : asset('storage/' . $photo->url))
                        : null,
                    'isMain' => $photo->is_main,
                ];
            }),
            // Contract fields (read-only for member)
            'contractStartDate' => $player->contract_start_date,
            'contractEndDate' => $player->contract_end_date,
            'contractDuration' => $player->contract_duration,
            'contractStatus' => $player->contract_status,
            'contractFees' => $player->contract_fees,
            'contractFeesType' => $player->contract_fees_type,
            'contractType' => $player->contract_type,
            'contractNature' => $player->contract_nature,
            // Ratings
            'rating' => $player->rating,
            'fitnessRating' => $player->fitness_rating,
            'speedRating' => $player->speed_rating,
            'techniqueRating' => $player->technique_rating,
            'isVerified' => $player->is_verified,
            'isRisingTalent' => $player->is_rising_talent,
            'topAgentPick' => $player->top_agent_pick,
            'isVisible' => $player->is_visible,
            'isApproved' => $player->is_approved,
            // Associations
            'user_id' => $player->user_id,
            // Timestamps
            'createdAt' => $player->created_at?->toISOString(),
            'updatedAt' => $player->updated_at?->toISOString(),
        ];
    }

    /**
     * Look up a player CV by phone number or national ID
     */
    public function getPlayerCV(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        // 0. Primary: Check if already linked via user_id
        $player = Player::where('user_id', $user->id)->first();

        // Fallback: search by national_id if provided
        if (!$player && $request->filled('national_id')) {
            $player = Player::where('national_id', $request->national_id)->first();
        }

        // Fallback: search by phone
        if (!$player && $request->filled('phone')) {
            $inputPhone = $request->phone;
            $phoneNumber = preg_replace('/\D/', '', $inputPhone);
            
            \Log::info("CV Lookup: User {$user->id}, Input: {$inputPhone}, Cleaned: {$phoneNumber}");

            // 1. Try last 8 digits (most robust)
            if (strlen($phoneNumber) >= 8) {
                $suffix = substr($phoneNumber, -8);
                $player = Player::whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '+', ''), '-', ''), '(', ''), ')', '') LIKE ?", ['%' . $suffix])->first();
                if ($player) \Log::info("Matched by suffix: {$suffix}");
            }
            
            // 2. Try full digit match in DB
            if (!$player) {
                $player = Player::whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '+', ''), '-', ''), '(', ''), ')', '') LIKE ?", ['%' . $phoneNumber . '%'])->first();
                if ($player) \Log::info("Matched by full digits: {$phoneNumber}");
            }
            
            // 3. Simple LIKE fallback
            if (!$player) {
                $player = Player::where('phone', 'LIKE', '%' . $phoneNumber . '%')->first();
                if ($player) \Log::info("Matched by simple LIKE: {$phoneNumber}");
            }
        }

        if (!$player) {
            \Log::warning("CV Lookup FAILED for User {$user->id}");
            return $this->error(__('api.cv_not_found'), 404);
        }

        // Auto-link if found and not linked
        if (!$player->user_id) {
            $player->user_id = $user->id;
            $player->save();
            \Log::info("Auto-linked User {$user->id} to Player CV {$player->id}");
        }

        return $this->success(new PlayerResource($player->load(['photos', 'mainPhoto', 'agent', 'contracts', 'documents', 'club', 'certificates'])));
    }

    /**
     * Create a new CV for the member
     */
    public function createPlayerCV(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string',
            'profile_role' => 'required|string|in:PLAYER,COACH,ADMINISTRATOR,REFEREE,PHOTOGRAPHER,DESIGNER',
            'sport' => 'required_unless:profile_role,DESIGNER|nullable|string',
            'gender' => 'required|string',
            'designer_type' => 'required_if:profile_role,DESIGNER|nullable|string',
        ]);

        $user = Auth::user();

        // Check if one already exists for this user
        if (Player::where('user_id', $user->id)->exists()) {
            return $this->error('CV already exists for this account', 400);
        }

        $allowedFields = [
            'name', 'name_ar', 'email', 'phone', 'address', 'national_id',
            'nationality', 'nationality_ar', 'date_of_birth', 'gender',
            'legal_status', 'sport', 'positions', 'club_id', 'national_team_id', 'club', 'club_ar',
            'club_logo', 'jersey_number', 'preferred_foot', 'deal_status',
            'market_value', 'height', 'weight', 'previous_clubs', 'previous_clubs_ar',
            'achievements', 'achievements_ar', 'current_stats',
            'bio', 'bio_ar', 'youtube_url', 'transfermarkt_url',
            'instagram_url', 'drive_url', 'notes', 'notes_ar', 'born_in_kuwait', 'profile_role',
            'designer_type', 'certificates',
            'volleyball_stats_pdf', 'volleyball_ranking_image',
            'volleyball_spike_reach', 'volleyball_block_reach',
        ];

        $user = Auth::user();
        $data = $request->only($allowedFields);

        // Normalize camelCase to snake_case for common player fields
        $mappings = [
            'bornInKuwait' => 'born_in_kuwait',
            'nationalId' => 'national_id',
            'nameAr' => 'name_ar',
            'nationalityAr' => 'nationality_ar',
            'dateOfBirth' => 'date_of_birth',
            'legalStatus' => 'legal_status',
            'marketValue' => 'market_value',
            'jerseyNumber' => 'jersey_number',
            'preferredFoot' => 'preferred_foot',
            'dealStatus' => 'deal_status',
            'clubId' => 'club_id',
            'nationalTeamId' => 'national_team_id',
            'previousClubs' => 'previous_clubs',
            'previousClubsAr' => 'previous_clubs_ar',
            'achievementsAr' => 'achievements_ar',
            'youtubeUrl' => 'youtube_url',
            'transfermarktUrl' => 'transfermarkt_url',
            'instagramUrl' => 'instagram_url',
            'driveUrl' => 'drive_url',
        ];

        foreach ($mappings as $camel => $snake) {
            if ($request->has($camel)) {
                $data[$snake] = $request->input($camel);
            }
        }
        
        // Force contract nature to NOT_JOINED for new member CVs
        $data['contract_nature'] = 'NOT_JOINED';
        $data['user_id'] = Auth::id();
        // Default to user's email/phone if not provided
        $data['email'] = $data['email'] ?? $user->email;
        $data['phone'] = $data['phone'] ?? $user->phone;
        
        $data['is_visible'] = false;
        $data['is_approved'] = false;

        // Map legacy club name fields
        if (array_key_exists('club', $data)) {
            $data['club_name_legacy'] = $data['club'];
            unset($data['club']);
        }
        if (array_key_exists('club_ar', $data)) {
            $data['club_name_ar_legacy'] = $data['club_ar'];
            unset($data['club_ar']);
        }

        $player = Player::create($data);

        if (isset($data['certificates'])) {
            $this->syncCertificates($player, is_string($data['certificates']) ? json_decode($data['certificates'], true) : $data['certificates']);
        }

        return $this->success($this->buildPlayerData($player), __('api.cv_created'), 201);
    }

    /**
     * Update a player's CV data (member can edit everything EXCEPT contract fields)
     */
    public function updatePlayerCV(Request $request, Player $player): JsonResponse
    {
        $allowedFields = [
            'name', 'name_ar', 'email', 'phone', 'address', 'national_id',
            'nationality', 'nationality_ar', 'date_of_birth', 'gender',
            'legal_status', 'sport', 'positions', 'club_id', 'national_team_id', 'club', 'club_ar',
            'club_logo', 'jersey_number', 'preferred_foot', 'deal_status',
            'market_value', 'height', 'weight', 'previous_clubs', 'previous_clubs_ar',
            'achievements', 'achievements_ar', 'current_stats',
            'bio', 'bio_ar', 'youtube_url', 'transfermarkt_url',
            'instagram_url', 'drive_url', 'notes', 'notes_ar', 'born_in_kuwait', 'profile_role',
            'designer_type', 'club_contracts', 'sponsors', 'certificates',
            'volleyball_stats_pdf', 'volleyball_ranking_image',
            'volleyball_spike_reach', 'volleyball_block_reach',
        ];

        $user = Auth::user();
        if ($player->user_id !== $user->id && $user->role !== 'OWNER' && $user->role !== 'ADMIN') {
            return $this->error('Unauthorized', 403);
        }

        $data = $request->only($allowedFields);

        // Normalize camelCase to snake_case for common player fields
        $mappings = [
            'bornInKuwait' => 'born_in_kuwait',
            'nationalId' => 'national_id',
            'nameAr' => 'name_ar',
            'nationalityAr' => 'nationality_ar',
            'dateOfBirth' => 'date_of_birth',
            'legalStatus' => 'legal_status',
            'marketValue' => 'market_value',
            'jerseyNumber' => 'jersey_number',
            'preferredFoot' => 'preferred_foot',
            'dealStatus' => 'deal_status',
            'clubId' => 'club_id',
            'nationalTeamId' => 'national_team_id',
            'previousClubs' => 'previous_clubs',
            'previousClubsAr' => 'previous_clubs_ar',
            'achievementsAr' => 'achievements_ar',
            'youtubeUrl' => 'youtube_url',
            'transfermarktUrl' => 'transfermarkt_url',
            'instagramUrl' => 'instagram_url',
            'driveUrl' => 'drive_url',
        ];

        foreach ($mappings as $camel => $snake) {
            if ($request->has($camel)) {
                $data[$snake] = $request->input($camel);
            }
        }

        // Map legacy club name fields only if provided as non-null
        if (array_key_exists('club', $data)) {
            $data['club_name_legacy'] = $data['club'];
            unset($data['club']);
        }
        if (array_key_exists('club_ar', $data)) {
            $data['club_name_ar_legacy'] = $data['club_ar'];
            unset($data['club_ar']);
        }

        // When club_id is explicitly cleared, force-clear legacy club name fields too
        if (array_key_exists('club_id', $data) && empty($data['club_id'])) {
            $data['club_id'] = null;
            $data['club_name_legacy'] = null;
            $data['club_name_ar_legacy'] = null;
        }

        // Handle array fields
        if (isset($data['positions']) && is_string($data['positions'])) {
            $data['positions'] = json_decode($data['positions'], true);
        }
        if (isset($data['previous_clubs']) && is_string($data['previous_clubs'])) {
            $data['previous_clubs'] = json_decode($data['previous_clubs'], true);
        }
        if (isset($data['previous_clubs_ar']) && is_string($data['previous_clubs_ar'])) {
            $data['previous_clubs_ar'] = json_decode($data['previous_clubs_ar'], true);
        }
        if (isset($data['achievements']) && is_string($data['achievements'])) {
            $data['achievements'] = json_decode($data['achievements'], true);
        }
        if (isset($data['achievements_ar']) && is_string($data['achievements_ar'])) {
            $data['achievements_ar'] = json_decode($data['achievements_ar'], true);
        }

        $player->update($data);

        if (isset($data['sponsors'])) {
            $player->sponsors()->sync($data['sponsors']);
        }

        if (isset($data['club_contracts'])) {
            $this->syncClubContracts($player, is_string($data['club_contracts']) ? json_decode($data['club_contracts'], true) : $data['club_contracts']);
        }

        if (isset($data['certificates'])) {
            $this->syncCertificates($player, is_string($data['certificates']) ? json_decode($data['certificates'], true) : $data['certificates']);
        }

        return $this->success($this->buildPlayerData($player), __('api.cv_updated'));
    }

    private function syncClubContracts(Player $player, array $contracts): void
    {
        $existingIds = $player->clubContracts()->pluck('id')->toArray();
        $newIds = collect($contracts)->pluck('id')->filter()->toArray();

        // Delete removed contracts
        $toDelete = array_diff($existingIds, $newIds);
        if (!empty($toDelete)) {
            $player->clubContracts()->whereIn('id', $toDelete)->delete();
        }

        // Update or create
        foreach ($contracts as $contractData) {
            unset($contractData['club_info']); // Remove extra info from frontend if any
            unset($contractData['file']);      // Remove UI-specific file field
            
            if (isset($contractData['id']) && $contractData['id']) {
                $player->clubContracts()->where('id', $contractData['id'])->update($contractData);
            } else {
                $player->clubContracts()->create($contractData);
            }
        }
    }

    private function syncCertificates(Player $player, array $certificates): void
    {
        $existingIds = \DB::table('coach_certificates')->where('coach_id', $player->id)->pluck('id')->toArray();
        $newIds = collect($certificates)->pluck('id')->filter()->toArray();

        // Delete removed certificates
        $toDelete = array_diff($existingIds, $newIds);
        if (!empty($toDelete)) {
            \DB::table('coach_certificates')->whereIn('id', $toDelete)->delete();
        }

        // Update or create
        foreach ($certificates as $certData) {
            unset($certData['file']);
            
            // Clean up UI specific fields
            unset($certData['uid']);
            
            $id = $certData['id'] ?? null;
            unset($certData['id']);

            if ($id) {
                \DB::table('coach_certificates')->where('id', $id)->update($certData);
            } else {
                $certData['coach_id'] = $player->id;
                $certData['created_at'] = now();
                $certData['updated_at'] = now();
                \DB::table('coach_certificates')->insert($certData);
            }
        }
    }

    /**
     * Upload player photo for member's CV
     */
    public function uploadPhoto(Request $request, Player $player): JsonResponse
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120'
        ]);

        $path = $this->mediaService->upload($request->file('photo'), 'players/photos');

        $photo = $player->photos()->create([
            'url' => $path,
            'is_main' => $player->photos()->count() === 0,
        ]);

        return $this->success($photo, __('api.photo_uploaded'));
    }

    /**
     * Upload CV document for member's player
     */
    public function uploadCVDocument(Request $request, Player $player): JsonResponse
    {
        $request->validate([
            'cv' => 'required|file|mimes:pdf,doc,docx|max:51200'
        ]);

        if ($player->cv_url) {
            $this->mediaService->delete($player->cv_url);
        }

        $path = $this->mediaService->upload($request->file('cv'), 'players/cvs');
        $player->update(['cv_url' => $path]);

        return $this->success(['cv_url' => asset('storage/' . $path)], __('api.cv_document_uploaded'));
    }

    /**
     * Delete a player photo
     */
    public function deletePhoto(Request $request, Player $player, $photoId): JsonResponse
    {
        $user = Auth::user();
        if ($player->user_id !== $user->id && $user->role !== 'OWNER' && $user->role !== 'ADMIN') {
            return $this->error('Unauthorized', 403);
        }

        $photo = $player->photos()->findOrFail($photoId);
        
        // Delete from storage
        if ($photo->url) {
            $this->mediaService->delete($photo->url);
        }
        
        $photo->delete();

        return $this->success(null, __('api.photo_deleted'));
    }

    /**
     * Upload certificate file for member's player
     */
    public function uploadCertificateFile(Request $request, Player $player, $certificateId): JsonResponse
    {
        $user = Auth::user();
        
        // Allow if it's the owner of the CV, regardless of role, or if they are OWNER/ADMIN
        if ($player->user_id !== $user->id && $user->role !== 'OWNER' && $user->role !== 'ADMIN') {
            return $this->error(__('api.unauthorized'), 403);
        }

        $request->validate([
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png,webp|max:10240'
        ]);

        $certificate = $player->certificates()->findOrFail($certificateId);

        if ($certificate->certificate_file) {
            $this->mediaService->delete($certificate->certificate_file);
        }
        
        $path = $this->mediaService->upload($request->file('file'), 'players/certificates');
        $certificate->update(['certificate_file' => $path]);
        
        return $this->success(['certificate_file' => asset('storage/' . $path)], __('api.certificate_file_uploaded'));
    }

    /**
     * Upload volleyball stats PDF for member's player
     */
    public function uploadVolleyballStatsPdf(Request $request, Player $player): JsonResponse
    {
        $user = Auth::user();
        if ($player->user_id !== $user->id && $user->role !== 'OWNER' && $user->role !== 'ADMIN') {
            return $this->error('Unauthorized', 403);
        }

        $request->validate([
            'file' => 'required|file|mimes:pdf|max:10240'
        ]);

        // Delete old file if exists
        if ($player->volleyball_stats_pdf) {
            $this->mediaService->delete($player->volleyball_stats_pdf);
        }

        $path = $this->mediaService->upload($request->file('file'), 'players/volleyball');
        $player->update(['volleyball_stats_pdf' => $path]);

        return $this->success(['volleyball_stats_pdf' => asset('storage/' . $path)], 'Volleyball stats PDF uploaded successfully');
    }

    /**
     * Upload volleyball ranking image for member's player
     */
    public function uploadVolleyballRankingImage(Request $request, Player $player): JsonResponse
    {
        $user = Auth::user();
        if ($player->user_id !== $user->id && $user->role !== 'OWNER' && $user->role !== 'ADMIN') {
            return $this->error('Unauthorized', 403);
        }

        $request->validate([
            'file' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120'
        ]);

        // Delete old file if exists
        if ($player->volleyball_ranking_image) {
            $this->mediaService->delete($player->volleyball_ranking_image);
        }

        $path = $this->mediaService->upload($request->file('file'), 'players/volleyball');
        $player->update(['volleyball_ranking_image' => $path]);

        return $this->success(['volleyball_ranking_image' => asset('storage/' . $path)], 'Volleyball ranking image uploaded successfully');
    }

    /**
     * Delete volleyball stats PDF
     */
    public function deleteVolleyballStatsPdf(Request $request, Player $player): JsonResponse
    {
        $user = Auth::user();
        if ($player->user_id !== $user->id && $user->role !== 'OWNER' && $user->role !== 'ADMIN') {
            return $this->error('Unauthorized', 403);
        }

        if ($player->volleyball_stats_pdf) {
            $this->mediaService->delete($player->volleyball_stats_pdf);
        }

        $player->update(['volleyball_stats_pdf' => null]);
        return $this->success(null, 'Volleyball stats PDF deleted successfully');
    }

    /**
     * Delete volleyball ranking image
     */
    public function deleteVolleyballRankingImage(Request $request, Player $player): JsonResponse
    {
        $user = Auth::user();
        if ($player->user_id !== $user->id && $user->role !== 'OWNER' && $user->role !== 'ADMIN') {
            return $this->error('Unauthorized', 403);
        }

        if ($player->volleyball_ranking_image) {
            $this->mediaService->delete($player->volleyball_ranking_image);
        }

        $player->update(['volleyball_ranking_image' => null]);
        return $this->success(null, 'Volleyball ranking image deleted successfully');
    }
}
