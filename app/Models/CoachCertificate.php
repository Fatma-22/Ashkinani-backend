<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoachCertificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'coach_id',
        'certificate_name',
        'certificate_type',
        'issuing_body',
        'year_obtained',
        'level',
        'certificate_number',
        'source_type',
        'certificate_file',
    ];

    /**
     * Get the coach that owns the certificate.
     */
    public function coach()
    {
        return $this->belongsTo(Player::class, 'coach_id');
    }
}
