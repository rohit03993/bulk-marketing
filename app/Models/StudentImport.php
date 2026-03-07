<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'academic_session_id',
        'import_class_name',
        'import_section_name',
        'original_filename',
        'file_path',
        'status',
        'duplicate_phone_policy',
        'total_rows',
        'processed_rows',
        'error_message',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function academicSession()
    {
        return $this->belongsTo(AcademicSession::class);
    }

    public function columnMappings()
    {
        return $this->hasMany(StudentImportColumn::class);
    }
}

