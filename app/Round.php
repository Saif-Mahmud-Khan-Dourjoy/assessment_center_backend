<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Round extends Model
{
    protected $table = 'rounds';
    protected $fillable =[
        'name', 'institute_id', 'passing_criteria', 'number', 'created_by', 'updated_by',
    ];

}
