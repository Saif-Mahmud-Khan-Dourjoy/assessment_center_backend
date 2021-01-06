<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RoundCandidates extends Model
{
    protected $table= 'round_candidates';

    protected $fillable=[
        'round_id','student_id', 'mark',
    ];

}
