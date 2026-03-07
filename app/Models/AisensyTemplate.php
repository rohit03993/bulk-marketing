<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AisensyTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'param_count',
        'param_mappings',
        'body',
    ];

    protected $casts = [
        'param_mappings' => 'array',
    ];

    public function getParamSources(): array
    {
        $mappings = $this->param_mappings ?? [];

        // Normalize to a simple ordered array: [source1, source2, ...]
        $sources = [];

        for ($i = 0; $i < (int) $this->param_count; $i++) {
            $sources[] = $mappings[$i] ?? null;
        }

        return $sources;
    }
}

