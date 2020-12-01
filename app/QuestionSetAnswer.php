<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionSetAnswer extends Model
{
    protected $table = 'question_set_answers';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'question_set_id', 'profile_id', 'time_taken', 'total_mark'
    ];

    /**
     * @return HasMany
     */
    public function question_set_answer_details(){
        return $this->hasMany('App\QuestionSetAnswerDetail', 'question_set_answer_id');
    }

    /**
     * @return BelongsTo
     */
    public function user_profile(){
        return $this->belongsTo('App\UserProfile', 'profile_id');
    }

    /**
     * @return BelongsTo
     */
    public function question_set(){
        return $this->belongsTo('App\QuestionSet', 'question_set_id');
    }
}
