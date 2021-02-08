<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class QuestionSetCandidate extends Model
{
    protected $table = 'question_set_candidates';
    protected $fillable =[
        'question_set_id', 'profile_id','attended'
    ];

    public function user_profile(){
        return $this->belongsTo('App\UserProfile','profile_id');
    }
    public function academic_info(){
        return $this->belongsTo('App\UserAcademicHistory','profile_id','profile_id');
    }
    public function question_set(){
        return $this->belongsTo('App\QuestionSet','question_set_id','id');
    }
}
