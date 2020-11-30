<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionSet extends Model
{
    protected $table = 'question_sets';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title', 'type', 'institute_id', 'institute', 'assessment_time', 'total_question', 'total_mark', 'status', 'privacy', 'created_by', 'approved_by'
    ];

    /**
     * @return HasMany
     */
    public function question_set_details(){
        return $this->hasMany('App\QuestionSetDetail', 'question_set_id');
    }
}
