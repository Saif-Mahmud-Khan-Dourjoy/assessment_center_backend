<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionSetAnswerDetail extends Model
{
    protected $table = 'question_set_answer_details';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'question_set_answer_id', 'question_id', 'answer', 'mark'
    ];

    /**
     * @return HasMany
     */
    public function question_set_answer(){
        return $this->hasMany('App\QuestionSetAnswer', 'question_set_answer_id');
    }
}
