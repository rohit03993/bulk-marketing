<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'academic_session_id',
        'class_name',
        'section_name',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function academicSession()
    {
        return $this->belongsTo(AcademicSession::class);
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function getFullNameAttribute(): string
    {
        $section = $this->section_name ? ' - '.$this->section_name : '';

        return $this->class_name.$section;
    }
}

