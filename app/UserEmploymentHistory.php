<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserEmploymentHistory extends Model
{
    protected $table = 'user_employment_histories';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'profile_id', 'institute', 'position', 'responsibility', 'start_date', 'end_date', 'duration', 'currently_work', 'description', 'check_status'
    ];

    /**
     * @return HasMany
     */
    public function userProfile(){
        return $this->hasMany('App\UserProfile', 'id');
    }
}
