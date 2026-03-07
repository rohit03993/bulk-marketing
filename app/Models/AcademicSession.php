<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcademicSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'starts_at',
        'ends_at',
        'is_current',
    ];

    protected $casts = [
        'starts_at' => 'date',
        'ends_at' => 'date',
        'is_current' => 'boolean',
    ];

    public function classSections()
    {
        return $this->hasMany(ClassSection::class);
    }
}

