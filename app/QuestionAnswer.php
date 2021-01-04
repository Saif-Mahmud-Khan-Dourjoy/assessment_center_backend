<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionAnswer extends Model
{
    protected $table = 'question_answers';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'question_id', 'answer', 'reference'
    ];


    /**
     * @return BelongsTo
     */
    public function question(){
        return $this->belongsTo('App\Question', 'question_id');
    }


}
