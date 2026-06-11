<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgramGroup extends Model
{
    protected $fillable = ['name'];

    public function subjects()
    {
        return $this->hasMany(Subject::class);
    }
}
