<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Player extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'agent_id',
        'scout_id',
        'club_id',
        'national_team_id',
        'profile_role',
        'designer_type',
        'name',
        'name_ar',
        'email',
        'phone',
        'address',
        'national_id',
        'nationality',
        'nationality_ar',
        'date_of_birth',
        'gender',
        'legal_status',
        'sport',
        'positions',
        'club_name_legacy',
        'club_name_ar_legacy',
        'club_logo',
        'jersey_number',
        'preferred_foot',
        'deal_status',
        'market_value',
        'height',
        'weight',
        'previous_clubs',
        'previous_clubs_ar',
        'achievements',
        'achievements_ar',
        'current_stats',
        'bio',
        'bio_ar',
        'youtube_url',
        'transfermarkt_url',
        'instagram_url',
        'drive_url',
        'volleyball_stats_pdf',
        'volleyball_ranking_image',
        'strategy_pdf',
        'volleyball_spike_reach',
        'volleyball_block_reach',
        'notes',
        'notes_ar',
        'contract_start_date',
        'contract_end_date',
        'contract_duration',
        'contract_status',
        'contract_fees',
        'contract_fees_type',
        'contract_type',
        'contract_nature',
        'cv_url',
        'visibility_settings',
        'is_visible',
        'share_token',
        'born_in_kuwait',
        'is_approved',
        'rating',
        'fitness_rating',
        'speed_rating',
        'technique_rating',
        'is_verified',
        'is_rising_talent',
        'top_agent_pick',
        'technical_report',
        'slug'
    ];

    protected $casts = [
        'date_of_birth' => 'string',
        'contract_start_date' => 'date',
        'contract_end_date' => 'date',
        'contract_duration' => 'decimal:2',
        'contract_fees' => 'decimal:2',
        'market_value' => 'decimal:2',
        'signing_bonus' => 'decimal:2',
        'is_visible' => 'boolean',
        'is_approved' => 'boolean',
        'positions' => 'array',
        'previous_clubs' => 'array',
        'previous_clubs_ar' => 'array',
        'achievements' => 'array',
        'achievements_ar' => 'array',
        'current_stats' => 'array',
        'visibility_settings' => 'array',
        'born_in_kuwait' => 'boolean',
        'rating' => 'integer',
        'fitness_rating' => 'integer',
        'speed_rating' => 'integer',
        'technique_rating' => 'integer',
        'volleyball_spike_reach' => 'integer',
        'volleyball_block_reach' => 'integer',
        'is_verified' => 'boolean',
        'is_rising_talent' => 'boolean',
        'top_agent_pick' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    public function scout()
    {
        return $this->belongsTo(Admin::class, 'scout_id');
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function photos()
    {
        return $this->hasMany(PlayerPhoto::class);
    }

    public function mainPhoto()
    {
        return $this->hasOne(PlayerPhoto::class)->where('is_main', true);
    }

    public function documents()
    {
        return $this->hasMany(PlayerDocument::class);
    }

    public function financialRecords()
    {
        return $this->morphMany(FinancialRecord::class, 'related');
    }

    public function deals()
    {
        return $this->hasMany(Deal::class);
    }

    public function sponsors()
    {
        return $this->belongsToMany(Sponsor::class, 'player_sponsor');
    }

    public function physicalReports()
    {
        return $this->hasMany(PlayerPhysicalReport::class);
    }

    public function progressPhotos()
    {
        return $this->hasMany(PlayerProgressPhoto::class);
    }

    public function nutritionPrograms()
    {
        return $this->hasMany(NutritionProgram::class);
    }

    public function trainingPrograms()
    {
        return $this->hasMany(TrainingProgram::class);
    }

    public function clubContracts()
    {
        return $this->hasMany(ClubContract::class);
    }

    public function club()
    {
        return $this->belongsTo(Club::class);
    }

    public function nationalTeam()
    {
        return $this->belongsTo(Club::class, 'national_team_id');
    }

    public function certificates()
    {
        return $this->hasMany(CoachCertificate::class, 'coach_id');
    }

    protected static function booted()
    {
        static::saving(function ($player) {
            $hasClub = !empty($player->club_id) || (!empty($player->club_name_legacy) && trim($player->club_name_legacy) !== '');

            if ($hasClub) {
                // Player has a club → must be SIGNED
                if (in_array($player->deal_status, ['FREE_AGENT', 'FREE_AGENT_COACH', null, ''])) {
                    $player->deal_status = 'SIGNED';
                }
            } else {
                // No club → must be FREE_AGENT (if was SIGNED)
                if ($player->deal_status === 'SIGNED') {
                    // Determine correct free-agent status by profile role
                    $player->deal_status = ($player->profile_role === 'COACH') ? 'FREE_AGENT_COACH' : 'FREE_AGENT';
                }
            }
        });

        static::deleting(function ($player) {
            // Delete physical files and child relations to prevent storage leaks and orphaned data
            
            // Delete CV if exists
            if ($player->cv_url) {
                static::deleteFile($player->cv_url);
            }

            // Delete club logo if exists
            if ($player->club_logo) {
                static::deleteFile($player->club_logo);
            }

            // Delete volleyball_stats_pdf if exists
            if ($player->volleyball_stats_pdf) {
                static::deleteFile($player->volleyball_stats_pdf);
            }

            // Delete volleyball_ranking_image if exists
            if ($player->volleyball_ranking_image) {
                static::deleteFile($player->volleyball_ranking_image);
            }

            // Delete strategy_pdf if exists
            if ($player->strategy_pdf) {
                static::deleteFile($player->strategy_pdf);
            }

            // Delete photos and their physical files
            foreach ($player->photos as $photo) {
                if ($photo->url) {
                    static::deleteFile($photo->url);
                }
                $photo->delete();
            }

            // Delete documents and their physical files
            foreach ($player->documents as $doc) {
                if ($doc->url) {
                    static::deleteFile($doc->url);
                }
                $doc->delete();
            }

            // Delete club contracts and their physical files
            foreach ($player->clubContracts as $cc) {
                if ($cc->file_url) {
                    static::deleteFile($cc->file_url);
                }
                $cc->delete();
            }

            // Delete certificates and their physical files
            foreach ($player->certificates as $cert) {
                if ($cert->certificate_file) {
                    static::deleteFile($cert->certificate_file);
                }
                $cert->delete();
            }

            // Delete physical reports and their files
            foreach ($player->physicalReports as $pr) {
                if ($pr->file_url) {
                    static::deleteFile($pr->file_url);
                }
                if ($pr->image_url) {
                    static::deleteFile($pr->image_url);
                }
                $pr->delete();
            }

            // Delete nutrition programs and their files
            foreach ($player->nutritionPrograms as $np) {
                if ($np->file_url) {
                    static::deleteFile($np->file_url);
                }
                if ($np->image_url) {
                    static::deleteFile($np->image_url);
                }
                $np->delete();
            }

            // Delete training programs and their files
            foreach ($player->trainingPrograms as $tp) {
                if ($tp->file_url) {
                    static::deleteFile($tp->file_url);
                }
                if ($tp->image_url) {
                    static::deleteFile($tp->image_url);
                }
                $tp->delete();
            }

            // Delete progress photos and their files
            foreach ($player->progressPhotos as $pp) {
                if ($pp->photo_url) {
                    static::deleteFile($pp->photo_url);
                }
                $pp->delete();
            }
        });
    }

    private static function deleteFile($path)
    {
        if (filter_var($path, FILTER_VALIDATE_URL) || str_starts_with($path, 'data:')) {
            return;
        }
        
        try {
            $cleanedPath = $path;
            if (str_starts_with($cleanedPath, 'storage/')) {
                $cleanedPath = substr($cleanedPath, 8);
            }
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($cleanedPath)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($cleanedPath);
            }
        } catch (\Exception $e) {
            \Log::error("Failed to delete player file: " . $e->getMessage());
        }
    }
}
