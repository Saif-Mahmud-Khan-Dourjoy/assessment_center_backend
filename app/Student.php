<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Student extends Model
{
    protected $table = 'students';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'profile_id', 'completing_percentage', 'total_complete_assessment', 'approve_status', 'active_status', 'guard_name'
    ];

    /**
     * @return BelongsTo
     */
    public function user_profile(){
        return $this->belongsTo('App\UserProfile', 'profile_id');
    }
}
