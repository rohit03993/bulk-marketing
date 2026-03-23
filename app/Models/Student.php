<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'class_section_id',
        'name',
        'father_name',
        'roll_number',
        'admission_number',
        'whatsapp_phone_primary',
        'whatsapp_phone_secondary',
        'status',
        'lead_status',
        'assigned_to',
        'assigned_by',
        'assigned_at',
        'total_calls',
        'last_call_at',
        'last_call_status',
        'last_call_notes',
        'next_followup_at',
        'is_call_blocked',
        'blocked_reason',
        'blocked_at',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'assigned_at' => 'datetime',
        'last_call_at' => 'datetime',
        'next_followup_at' => 'datetime',
        'blocked_at' => 'datetime',
        'is_call_blocked' => 'boolean',
    ];

    public function classSection()
    {
        return $this->belongsTo(ClassSection::class);
    }

    public function school()
    {
        return $this->classSection?->school();
    }

    public function academicSession()
    {
        return $this->classSection?->academicSession();
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class)->withTimestamps();
    }

    public function calls()
    {
        return $this->hasMany(StudentCall::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function getWhatsappPhones(): array
    {
        $phones = [];

        if (! empty($this->whatsapp_phone_primary)) {
            $phones[] = $this->whatsapp_phone_primary;
        }

        if (! empty($this->whatsapp_phone_secondary)) {
            $phones[] = $this->whatsapp_phone_secondary;
        }

        return array_values(array_filter($phones));
    }

    /**
     * Normalize to Indian 10-digit (digits only). Accepts 10 digits, 91+10, or +91+10.
     * Returns 10-digit string or null if invalid. Indian mobile must start with 6, 7, 8 or 9.
     */
    public static function normalizeIndianPhone(?string $phone): ?string
    {
        if ($phone === null || $phone === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);

        if (strlen($digits) === 10) {
            $ten = $digits;
        } elseif (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            $ten = substr($digits, 2);
        } else {
            return null;
        }

        $first = (int) $ten[0];
        if ($first < 6 || $first > 9) {
            return null;
        }

        return $ten;
    }

    /**
     * Find a student that has this phone as primary or secondary (stored as 10 digits).
     */
    public static function findByPhone(string $phone): ?Student
    {
        $normalized = static::normalizeIndianPhone($phone);
        if ($normalized === null) {
            return null;
        }

        return static::where('whatsapp_phone_primary', $normalized)
            ->orWhere('whatsapp_phone_secondary', $normalized)
            ->first();
    }

    /**
     * Check if this phone is used by another student (optionally exclude one id).
     */
    public static function isPhoneUsedByOther(?string $phone, ?int $excludeStudentId = null): bool
    {
        $normalized = static::normalizeIndianPhone($phone);
        if ($normalized === null) {
            return false;
        }

        $query = static::where(function ($q) use ($normalized) {
            $q->where('whatsapp_phone_primary', $normalized)
                ->orWhere('whatsapp_phone_secondary', $normalized);
        });

        if ($excludeStudentId !== null) {
            $query->where('id', '!=', $excludeStudentId);
        }

        return $query->exists();
    }

    /** Format 10-digit for display as +91 XXXXX XXXXX */
    public static function formatPhoneForDisplay(?string $phone): string
    {
        $n = static::normalizeIndianPhone($phone);
        if ($n === null) {
            return $phone ?? '';
        }
        return '+91 '.substr($n, 0, 5).' '.substr($n, 5, 5);
    }
}

