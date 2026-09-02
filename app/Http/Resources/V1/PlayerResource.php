<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use App\Models\Federation;
use App\Models\Club;

class PlayerResource extends JsonResource
{
    /**
     * The "data" wrapper that should be applied.
     *
     * @var string|null
     */
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        $user = Auth::guard('sanctum')->user();
        
        // Match owner by phone suffix (last 8 digits) - consistent with MemberProfileController logic
        $isOwnerByPhone = false;
        if ($user && $user->phone && $this->phone) {
            $cleanedUserPhone = preg_replace('/\D/', '', $user->phone);
            $cleanedPlayerPhone = preg_replace('/\D/', '', $this->phone);
            
            if (strlen($cleanedUserPhone) >= 8 && strlen($cleanedPlayerPhone) >= 8) {
                $isOwnerByPhone = substr($cleanedUserPhone, -8) === substr($cleanedPlayerPhone, -8);
            } else if ($cleanedUserPhone && $cleanedPlayerPhone) {
                $isOwnerByPhone = $cleanedUserPhone === $cleanedPlayerPhone;
            }
        }

        // Grant privileged access if user is Owner/Admin/Agent OR if looking at their own record (by ID or phone) OR if via share token
        $isPrivileged = ($user && (
                            in_array($user->role, ['OWNER', 'ADMIN', 'AGENT']) || 
                            $user->id == $this->user_id || 
                            $isOwnerByPhone
                         )) || 
                         ($request->query('share_token') && $request->query('share_token') === $this->share_token);
        
        $visibilitySettings = $this->visibility_settings ?: [
            'nationality' => true,
            'age' => true,
            'dateOfBirth' => true,
            'positions' => true,
            'club' => true,
            'marketValue' => true,
            'preferredFoot' => true,
            'height' => true,
            'weight' => true,
            'jerseyNumber' => true,
            'previousClubs' => true,

            'dealStatus' => false,
            'contractInfo' => false,
            'photos' => true,
            'achievements' => true,
            'stats' => true,
            'sponsors' => true,
            'certificates' => true,
        ];
        $visibility = $visibilitySettings;

        // Force hide sensitive data for non-privileged users
        if (!$isPrivileged) {
            $visibility['dealStatus'] = false;
            $visibility['contractInfo'] = false;
        }

        // Public/Guest filtering logic
        $data = [
            'id' => $this->id,
            'role' => $this->profile_role,
            'designerType' => $this->designer_type,
            'name' => $this->name,
            'nameAr' => $this->name_ar,
            'sport' => $this->sport,
            'slug' => $this->slug,
            'federation_logo' => $this->sport ? (Federation::where('sport_name', $this->sport)->first()?->logo_url) : null,
            'dateOfBirth' => $this->date_of_birth,
            'bornInKuwait' => $this->born_in_kuwait,
            'mainPhoto' => new PlayerPhotoResource($this->whenLoaded('mainPhoto')),
        ];

        // Club logic
        $clubModel = null;
        if ($this->club_id) {
            if ($this->resource->relationLoaded('club')) {
                $clubModel = $this->resource->getRelation('club');
            }
            if (!$clubModel) {
                $clubModel = $this->resource->club()->first();
            }
        }
        $data['club_id'] = $this->club_id;
        $data['club_info'] = $this->resource->relationLoaded('club') ? new ClubResource($this->resource->club) : null;
        $data['auto_club_logo'] = ($clubModel instanceof Club) ? $clubModel->logo_url : null;

        // National team logic
        $nationalTeamModel = null;
        if ($this->national_team_id) {
            if ($this->resource->relationLoaded('nationalTeam')) {
                $nationalTeamModel = $this->resource->getRelation('nationalTeam');
            }
            if (!$nationalTeamModel) {
                $nationalTeamModel = $this->resource->nationalTeam()->first();
            }
        }
        $data['national_team_id'] = $this->national_team_id;
        $data['national_team'] = $nationalTeamModel ? [
            'id'       => $nationalTeamModel->id,
            'name'     => $nationalTeamModel->name,
            'name_ar'  => $nationalTeamModel->name_ar,
            'logo_url' => $nationalTeamModel->logo_url,
        ] : null;

        // Apply visibility flags or privilege check
        $this->applyVisibility($data, 'nationality', $this->nationality, $isPrivileged, $visibility);
        $this->applyVisibility($data, 'nationalityAr', $this->nationality_ar, $isPrivileged, $visibility, 'nationality');
        $data['bio'] = $this->bio;
        $data['bioAr'] = $this->bio_ar;
        // Smart YouTube URL handling: support legacy string and new JSON format
        $youtubeData = [];
        if ($this->youtube_url) {
            $decoded = json_decode($this->youtube_url, true);
            if (is_array($decoded)) {
                $youtubeData = $decoded;
            } else {
                // Backward compatibility for comma-separated links
                $links = explode(',', $this->youtube_url);
                foreach ($links as $link) {
                    $trimmed = trim($link);
                    if ($trimmed) {
                        $youtubeData[] = ['url' => $trimmed, 'title' => null];
                    }
                }
            }
        }
        $data['youtubeUrl'] = $youtubeData;
        $data['youtubeUrlLegacy'] = is_array($this->youtube_url) ? implode(',', array_column($this->youtube_url, 'url')) : $this->youtube_url;
        $data['transfermarktUrl'] = $this->transfermarkt_url;
        // For volleyball players, use volleynetUrl as the profile link label
        $data['volleynetUrl'] = ($this->sport === 'Volleyball' || $this->sport === 'Beach Volleyball')
            ? $this->transfermarkt_url
            : null;
        $data['driveUrl'] = $this->drive_url;
        $this->applyVisibility($data, 'age', $this->date_of_birth ? now()->year - (int) $this->date_of_birth : null, $isPrivileged, $visibility);
        $this->applyVisibility($data, 'positions', $this->positions, $isPrivileged, $visibility);
        $this->applyVisibility($data, 'club', $this->club_id ? ($clubModel?->name) : $this->club_name_legacy, $isPrivileged, $visibility);
        $this->applyVisibility($data, 'clubAr', $this->club_id ? ($clubModel?->name_ar) : $this->club_name_ar_legacy, $isPrivileged, $visibility, 'club');
        $clubLogoFormatted = $this->club_id ? ($clubModel?->logo_url) : ($this->club_logo ? (filter_var($this->club_logo, FILTER_VALIDATE_URL) ? $this->club_logo : asset('storage/' . $this->club_logo)) : null);
        $this->applyVisibility($data, 'clubLogo', $clubLogoFormatted, $isPrivileged, $visibility, 'club');
        $this->applyVisibility($data, 'marketValue', $this->market_value, $isPrivileged, $visibility);
        $this->applyVisibility($data, 'preferredFoot', $this->preferred_foot, $isPrivileged, $visibility);
        $this->applyVisibility($data, 'height', $this->height, $isPrivileged, $visibility);
        $this->applyVisibility($data, 'weight', $this->weight, $isPrivileged, $visibility);
        $this->applyVisibility($data, 'jerseyNumber', $this->jersey_number, $isPrivileged, $visibility);
        $this->applyVisibility($data, 'previousClubs', $this->previous_clubs, $isPrivileged, $visibility);
        $this->applyVisibility($data, 'previousClubsAr', $this->previous_clubs_ar, $isPrivileged, $visibility, 'previousClubs');
        $this->applyVisibility($data, 'gender', $this->gender, $isPrivileged, $visibility);
        $this->applyVisibility($data, 'legalStatus', $this->legal_status, $isPrivileged, $visibility);
        $this->applyVisibility($data, 'dealStatus', $this->deal_status, $isPrivileged, $visibility);



        if ($isPrivileged || ($visibility['stats'] ?? false)) {
            $data['stats'] = $this->current_stats;
        }

        if ($isPrivileged || ($visibility['achievements'] ?? false)) {
            $data['achievements'] = $this->achievements;
            $data['achievementsAr'] = $this->achievements_ar;
        }

        // Relations
        $data['agent'] = new AgentResource($this->whenLoaded('agent'));

        if ($isPrivileged || ($visibility['contractInfo'] ?? false)) {
            $data['contracts'] = ContractResource::collection($this->whenLoaded('contracts'));
            $data['contractStartDate'] = $this->contract_start_date;
            $data['contractEndDate'] = $this->contract_end_date;
            $data['contractDuration'] = $this->contract_duration;
            $data['contractStatus'] = $this->contract_status;
            $data['contractFees'] = $this->contract_fees;
            $data['contractFeesType'] = $this->contract_fees_type;
            $data['contractType'] = $this->contract_type;
            $data['contractNature'] = $this->contract_nature;
        }

        // CV is visible for coaches or if privileged
        if ($this->profile_role === 'COACH' || $isPrivileged || ($visibility['contractInfo'] ?? false)) {
            $data['cvUrl'] = $this->cv_url ? (
                (str_starts_with($this->cv_url, 'data:') || filter_var($this->cv_url, FILTER_VALIDATE_URL)) 
                    ? $this->cv_url 
                    : asset('storage/' . $this->cv_url)
            ) : null;
        }

        // Volleyball-specific files (publicly visible for volleyball players)
        $isVolleyball = in_array($this->sport, ['Volleyball', 'Beach Volleyball']);
        $data['volleyballStatsPdf'] = $this->volleyball_stats_pdf
            ? ((str_starts_with($this->volleyball_stats_pdf, 'data:') || filter_var($this->volleyball_stats_pdf, FILTER_VALIDATE_URL)) ? $this->volleyball_stats_pdf : asset('storage/' . $this->volleyball_stats_pdf))
            : null;
        $data['volleyballRankingImage'] = $this->volleyball_ranking_image
            ? ((str_starts_with($this->volleyball_ranking_image, 'data:') || filter_var($this->volleyball_ranking_image, FILTER_VALIDATE_URL)) ? $this->volleyball_ranking_image : asset('storage/' . $this->volleyball_ranking_image))
            : null;
        // Volleyball-specific jump/reach measurements (cm)
        $data['volleyballSpikeReach'] = $this->volleyball_spike_reach;
        $data['volleyballBlockReach'] = $this->volleyball_block_reach;

        // Strategy PDF — strictly confidential: only ADMIN/OWNER or the player themselves (no share token)
        $isStrategyPdfVisible = $user && (
            in_array($user->role, ['OWNER', 'ADMIN']) ||
            $user->id == $this->user_id ||
            $isOwnerByPhone
        );
        if ($isStrategyPdfVisible) {
            $data['strategyPdf'] = $this->strategy_pdf ? (
                (str_starts_with($this->strategy_pdf, 'data:') || filter_var($this->strategy_pdf, FILTER_VALIDATE_URL))
                    ? $this->strategy_pdf
                    : asset('storage/' . $this->strategy_pdf)
            ) : null;
        }

        if ($isPrivileged || ($visibility['photos'] ?? false)) {
            $data['photos'] = PlayerPhotoResource::collection($this->whenLoaded('photos'));
        }
        if ($isPrivileged) {
            $data['documents'] = PlayerDocumentResource::collection($this->whenLoaded('documents'));
        }
        if ($isPrivileged) {
            $data['notes'] = $this->notes;
            $data['notesAr'] = $this->notes_ar;
            $data['email'] = $this->email;
            $data['nationalId'] = $this->national_id;
            $data['phone'] = $this->phone;
            $data['address'] = $this->address;
            $data['instagramUrl'] = $this->instagram_url;
            $data['isVisible'] = $this->is_visible;
            $data['isApproved'] = $this->is_approved;
            $data['shareToken'] = $this->share_token;
            $data['technicalReport'] = $this->technical_report;

            // Scout info
            $data['scoutId'] = $this->scout_id;
            $data['scoutName'] = $this->relationLoaded('scout') && $this->scout ? $this->scout->name : null;
        }

        // Ratings & Badges (Public)
        $data['rating'] = $this->rating;
        $data['fitnessRating'] = $this->fitness_rating;
        $data['speedRating'] = $this->speed_rating;
        $data['techniqueRating'] = $this->technique_rating;
        $data['isVerified'] = $this->is_verified;
        $data['isRisingTalent'] = $this->is_rising_talent;
        $data['topAgentPick'] = $this->top_agent_pick;
        // Prepare certificates data
        $certificatesData = $this->resource->relationLoaded('certificates') ? $this->certificates->map(function ($cert) {
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
        }) : [];

        $data['certificates'] = $certificatesData;
        $data['sponsors'] = SponsorResource::collection($this->whenLoaded('sponsors'));

        if ($isPrivileged) {
            $data['clubContracts'] = ClubContractResource::collection($this->whenLoaded('clubContracts'));
        }

        $data['visibility'] = $visibility;
        $data['updatedAt'] = $this->updated_at;

        return $data;
    }

    private function applyVisibility(&$data, $key, $value, $isPrivileged, $visibility, $visibilityKey = null)
    {
        $vKey = $visibilityKey ?: $key;
        if ($isPrivileged || ($visibility[$vKey] ?? true)) {
            $data[$key] = $value;
        }
    }
}
