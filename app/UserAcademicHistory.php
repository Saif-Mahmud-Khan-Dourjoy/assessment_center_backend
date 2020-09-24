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
        'profile_id', 'exam_course_title', 'major', 'institute', 'result', 'passing_year', 'duration', 'description'
    ];

    /**
     * @return HasMany
     */
    public function userProfile(){
        return $this->hasMany('App\UserProfile', 'id');
    }
}
