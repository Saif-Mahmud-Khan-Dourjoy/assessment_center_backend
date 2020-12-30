<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionSetDetail extends Model
{
    protected $table = 'question_set_details';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'question_set_id', 'question_id', 'mark', 'question_time', 'partial_marking_status'
    ];

    /**
     * @return HasMany
     */
    public function question_set(){
        return $this->hasMany('App\QuestionSet', 'question_set_id');
    }
}
