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
        $suffix = match ((int) $this->grade) {
            9 => 'th', // will render as 9th
            default => 'th',
        };

        // Keep it simple: "11th (NEET)" style.
        return $this->grade . $suffix . ' (' . strtoupper(trim((string) $this->stream)) . ')';
    }
}

