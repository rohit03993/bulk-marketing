<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadClassPreset extends Model
{
    use HasFactory;

    protected $fillable = [
        'grade',
        'stream',
        'is_active',
        'display_order',
    ];

    protected $casts = [
        'grade' => 'integer',
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    public function getDisplayLabelAttribute(): string
    {
        $grade = (int) $this->grade;
        // English ordinal suffix (1st/2nd/3rd/4th... and special cases 11/12/13 => 'th')
        $lastTwo = $grade % 100;
        if (in_array($lastTwo, [11, 12, 13], true)) {
            $suffix = 'th';
        } else {
            $lastOne = $grade % 10;
            $suffix = match ($lastOne) {
                1 => 'st',
                2 => 'nd',
                3 => 'rd',
                default => 'th',
            };
        }

        $stream = strtoupper(trim((string) $this->stream));
        if ($stream === '') {
            return $grade . $suffix;
        }

        // Keep it simple: "11th (NEET)" style.
        return $grade . $suffix . ' (' . $stream . ')';
    }
}

