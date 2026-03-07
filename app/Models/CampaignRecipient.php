<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampaignRecipient extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'student_id',
        'phone',
        'status',
        'template_params',
        'message_sent',
        'provider_response',
        'error_message',
    ];

    protected $casts = [
        'template_params' => 'array',
        'provider_response' => 'array',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}

