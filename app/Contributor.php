<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contributor extends Model
{
    protected $table = 'contributors';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'profile_id', 'completing_percentage', 'total_question', 'average_rating', 'approve_status', 'active_status', 'guard_name'
    ];

    /**
     * @return BelongsTo
     */
    public function user_profile(){
        return $this->belongsTo('UserProfile');
    }
}
