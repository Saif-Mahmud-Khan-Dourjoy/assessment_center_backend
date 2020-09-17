<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
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
        'user_id', 'first_name', 'last_name', 'email', 'phone', 'skype', 'profession', 'about', 'image', 'address', 'zipcode', 'country', 'guard_name'
    ];

    /**
     * @return HasOne
     */
    public function contributor(){
        return $this->hasOne('Contributor');
    }
}
