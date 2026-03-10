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
        'tag_name',
        'original_filename',
        'file_path',
        'status',
        'duplicate_phone_policy',
        'total_rows',
        'processed_rows',
        'skipped_count',
        'skipped_rows',
        'error_message',
    ];

    protected $casts = [
        'skipped_rows' => 'array',
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

