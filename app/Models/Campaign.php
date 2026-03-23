<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'academic_session_id',
        'aisensy_template_id',
        'name',
        'media_type',
        'media_url',
        'media_filename',
        'status',
        'total_recipients',
        'sent_count',
        'failed_count',
        'scheduled_at',
        'started_at',
        'finished_at',
        'created_by',
        'shot_by',
        'shot_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'shot_at' => 'datetime',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function academicSession()
    {
        return $this->belongsTo(AcademicSession::class);
    }

    public function template()
    {
        return $this->belongsTo(AisensyTemplate::class, 'aisensy_template_id');
    }

    public function recipients()
    {
        return $this->hasMany(CampaignRecipient::class);
    }

    public function shotByUser()
    {
        return $this->belongsTo(User::class, 'shot_by');
    }
}

