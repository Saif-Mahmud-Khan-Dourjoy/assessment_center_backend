<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserAcademicHistory extends Model
{
    protected $table = 'user_academic_histories';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'profile_id', 'exam_course_title', 'major', 'institute', 'result', 'start_year', 'end_year', 'duration', 'description', 'check_status'
    ];

    /**
     * @return HasMany
     */
    public function userProfile(){
        return $this->hasMany('App\UserProfile', 'id');
    }
}
