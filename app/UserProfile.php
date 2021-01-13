<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UserProfile extends Model
{

    protected $table = 'user_profiles';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'institute_id', 'first_name', 'last_name', 'email', 'phone', 'skype', 'profession', 'skill', 'about', 'img', 'address', 'zipcode', 'country', 'guard_name'
    ];

    /**
     * @return HasOne
     */
    public function contributor(){
        return $this->hasOne('App\Contributor', 'profile_id');
    }

    /**
     * @return HasOne
     */
    public function student(){
        return $this->hasOne('App\Student', 'profile_id');
    }

    /**
     * @return HasMany
     */
    public function user_academic_history(){
        return $this->hasMany('App\UserAcademicHistory', 'profile_id');
    }

    /**
     * @return HasMany
     */
    public function user_employment_history(){
        return $this->hasMany('App\UserEmploymentHistory', 'profile_id');
    }

    /**
     * @return BelongsTo
     */
    public function user(){
        return $this->belongsTo('App\User', 'user_id');
    }
}
