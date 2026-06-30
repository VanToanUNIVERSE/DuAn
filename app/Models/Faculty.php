<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faculty extends Model
{
    protected $fillable = ['name'];

    // Một khoa có nhiều chuyên ngành
    public function majors()
    {
        return $this->hasMany(Major::class);
    }
}
