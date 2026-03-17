<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentCall extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'user_id',
        'call_status',
        'call_direction',
        'whatsapp_auto_status',
        'who_answered',
        'duration_minutes',
        'call_notes',
        'tags',
        'status_changed_to',
        'next_followup_at',
        'followup_notes',
        'called_at',
    ];

    protected $casts = [
        'called_at' => 'datetime',
        'next_followup_at' => 'datetime',
        'duration_minutes' => 'integer',
        'tags' => 'array',
    ];

    public static array $whoAnsweredOptions = [
        'student' => 'Student',
        'father' => 'Father',
        'mother' => 'Mother',
        'guardian' => 'Guardian',
        'other' => 'Other',
    ];

    public static array $quickTags = [
        'fee_query' => 'Fee Query',
        'course_info' => 'Course Info',
        'campus_visit' => 'Visit',
        'send_brochure' => 'Brochure',
        'documents_pending' => 'Documents',
        'will_discuss_family' => 'Family',
        'scholarship_query' => 'Scholarship',
        'ready_to_enroll' => 'Ready',
    ];

    // Call status constants (mirroring LeadCall)
    public const STATUS_CONNECTED = 'connected';
    public const STATUS_NO_ANSWER = 'no_answer';
    public const STATUS_BUSY = 'busy';
    public const STATUS_SWITCHED_OFF = 'switched_off';
    public const STATUS_NOT_REACHABLE = 'not_reachable';
    public const STATUS_WRONG_NUMBER = 'wrong_number';
    public const STATUS_CALLBACK = 'callback';

    public static array $callStatuses = [
        self::STATUS_CONNECTED => 'Connected',
        self::STATUS_NO_ANSWER => 'No Answer',
        self::STATUS_BUSY => 'Busy',
        self::STATUS_SWITCHED_OFF => 'Switched Off',
        self::STATUS_NOT_REACHABLE => 'Not Reachable',
        self::STATUS_WRONG_NUMBER => 'Wrong Number',
        self::STATUS_CALLBACK => 'Callback Requested',
    ];

    /** Statuses that count as "not connected" for cap (e.g. 3 attempts in 7 days). */
    public static function notConnectedStatuses(): array
    {
        return [
            self::STATUS_NO_ANSWER,
            self::STATUS_BUSY,
            self::STATUS_SWITCHED_OFF,
            self::STATUS_NOT_REACHABLE,
            self::STATUS_WRONG_NUMBER,
            self::STATUS_CALLBACK,
        ];
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

