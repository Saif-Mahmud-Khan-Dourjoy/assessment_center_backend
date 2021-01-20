<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoundCandidates extends Model
{
    protected $table= 'round_candidates';

    protected $fillable=[
        'round_id','student_id', 'mark',
    ];

    public function user_profiles(){
        return $this->belongsTo('App\UserProfile','student_id');
    }

}
