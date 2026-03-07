<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentImportColumn extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_import_id',
        'column_index',
        'column_name',
        'target_field',
    ];

    public function import()
    {
        return $this->belongsTo(StudentImport::class, 'student_import_id');
    }
}

