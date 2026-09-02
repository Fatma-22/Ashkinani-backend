<?php

namespace App\Http\Requests\Player;

use Illuminate\Foundation\Http\FormRequest;

class StorePlayerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Handle via Policies/Middleware
    }

    public function rules(): array
    {
        return [
            'profile_role' => ['sometimes', 'nullable', 'in:PLAYER,COACH,ADMINISTRATOR,REFEREE,PHOTOGRAPHER,DESIGNER'],
            'designer_type' => ['nullable', 'string', 'max:255'],
            'name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'national_id' => ['nullable', 'string', 'max:50'],
            'sport' => ['sometimes', 'nullable', 'string'],
            'nationality' => ['sometimes', 'nullable', 'string'],
            'nationality_ar' => ['nullable', 'string'],
            'date_of_birth' => ['sometimes', 'nullable', 'string', 'max:10'],
            'positions' => ['nullable', 'array'],
            'positions.*' => ['nullable', 'string'],
            'agent_id' => ['nullable', 'exists:agents,id'],
            'scout_id' => ['nullable', 'exists:admins,id'],
            'club_id' => ['nullable', 'exists:clubs,id'],
            'club' => ['nullable', 'string'],
            'club_ar' => ['nullable', 'string'],
            'market_value' => ['nullable', 'numeric', 'min:0'],
            'preferred_foot' => ['nullable', 'in:LEFT,RIGHT,BOTH'],
            'deal_status' => ['nullable', 'string'],
            'height' => ['nullable', 'integer', 'min:0'],
            'weight' => ['nullable', 'integer', 'min:0'],
            'volleyball_spike_reach' => ['nullable', 'integer', 'min:0', 'max:400'],
            'volleyball_block_reach' => ['nullable', 'integer', 'min:0', 'max:400'],
            'jersey_number' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
            'notes_ar' => ['nullable', 'string'],
            'bio' => ['nullable', 'string'],
            'bio_ar' => ['nullable', 'string'],
            'contract_start_date' => ['nullable', 'date'],
            'contract_end_date' => ['nullable', 'date'],
            'contract_duration' => ['nullable', 'numeric'],
            'contract_status' => ['nullable', 'in:ACTIVE,PENDING,EXPIRED,NEGOTIATION'],
            'contract_fees' => ['nullable', 'numeric', 'min:0'],
            'contract_fees_type' => ['nullable', 'in:FIXED,PERCENTAGE'],
            'contract_type' => ['nullable', 'in:PROFESSIONAL,YOUTH,LOAN,AMATEUR'],
            'gender' => ['nullable', 'in:MALE,FEMALE'],
            'legal_status' => ['nullable', 'in:PROFESSIONAL,AMATEUR'],
            'previous_clubs' => ['nullable', 'array'],
            'previous_clubs.*' => ['nullable', 'string'],
            'previous_clubs_ar' => ['nullable', 'array'],
            'previous_clubs_ar.*' => ['nullable', 'string'],
            'achievements' => ['nullable', 'array'],
            'achievements.*' => ['nullable', 'string'],
            'achievements_ar' => ['nullable', 'array'],
            'achievements_ar.*' => ['nullable', 'string'],
            'current_stats' => ['nullable', 'array'],
            'youtube_url' => ['nullable', 'string'],
            'transfermarkt_url' => ['nullable', 'url', 'max:2048'],
            'instagram_url' => ['nullable', 'url', 'max:2048'],
            'drive_url' => ['nullable', 'url', 'max:2048'],
            'visibility_settings' => ['nullable', 'array'],
            'is_visible' => ['sometimes', 'boolean'],
            'born_in_kuwait' => ['sometimes', 'boolean'],
            'contract_nature' => ['nullable', 'in:AUTHORIZATION,SIGNING,NOT_JOINED,TERMINATION'],
            'cv_url' => ['nullable', 'string', 'max:2048'],
            'strategy_pdf' => ['nullable', 'string', 'max:2048'],
            'rating' => ['nullable', 'integer', 'min:0', 'max:5'],
            'fitness_rating' => ['nullable', 'integer', 'min:0', 'max:5'],
            'speed_rating' => ['nullable', 'integer', 'min:0', 'max:5'],
            'technique_rating' => ['nullable', 'integer', 'min:0', 'max:5'],
            'is_verified' => ['sometimes', 'boolean'],
            'is_rising_talent' => ['sometimes', 'boolean'],
            'top_agent_pick' => ['sometimes', 'boolean'],
            'technical_report' => ['nullable', 'string'],
            'sponsors' => ['nullable', 'array'],
            'sponsors.*' => ['nullable', 'exists:sponsors,id'],
            'club_contracts' => ['nullable', 'array'],
            'club_contracts.*.id' => ['nullable', 'integer'],
            'club_contracts.*.club_name' => ['nullable', 'string', 'max:255'],
            'club_contracts.*.club_name_ar' => ['nullable', 'string', 'max:255'],
            'club_contracts.*.club_country' => ['nullable', 'string', 'max:255'],
            'club_contracts.*.club_country_ar' => ['nullable', 'string', 'max:255'],
            'club_contracts.*.start_date' => ['nullable', 'date'],
            'club_contracts.*.end_date' => ['nullable', 'date'],
            'club_contracts.*.notes' => ['nullable', 'string'],
            'club_contracts.*.notes_ar' => ['nullable', 'string'],
            'certificates' => ['nullable', 'array'],
            'certificates.*.id' => ['nullable', 'integer'],
            'certificates.*.certificate_name' => ['nullable', 'string', 'max:255'],
            'certificates.*.certificate_type' => ['nullable', 'string', 'in:Coaching License,Fitness / Conditioning,First Aid / CPR,Sports Psychology,Sports Nutrition,Sports Management,Sports Marketing,Sports Law,Referee License,VAR License,Media / Photography,Video Editing,Medical / Physiotherapy,Other'],
            'certificates.*.issuing_body' => ['nullable', 'string', 'max:255'],
            'certificates.*.year_obtained' => ['nullable', 'integer', 'min:1900', 'max:' . date('Y')],
            'certificates.*.level' => ['nullable', 'string', 'in:Beginner,Intermediate,Advanced,Professional'],
            'certificates.*.certificate_number' => ['nullable', 'string', 'max:100'],
            'certificates.*.source_type' => ['required_with:certificates', 'string', 'in:Sports Federation,Academy,University,Online Course,Club Training,Other'],
            'certificates.*.certificate_file' => ['nullable', 'string', 'max:2048'],
        ];
    }
}
