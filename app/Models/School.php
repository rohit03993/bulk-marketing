<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'short_name',
        'address',
        'city',
        'state',
        'contact_person',
        'contact_phone',
        'contact_email',
    ];

    public function classSections()
    {
        return $this->hasMany(ClassSection::class);
    }

    public function students()
    {
        return $this->hasManyThrough(Student::class, ClassSection::class);
    }

    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }
}

