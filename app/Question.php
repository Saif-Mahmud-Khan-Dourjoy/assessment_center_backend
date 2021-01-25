<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Question extends Model
{
    protected $table = 'questions';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'profile_id', 'institute_id', 'category_id', 'privacy', 'publish_status', 'question_type', 'question_text', 'description', 'option_type', 'no_of_option', 'no_of_answer', 'no_of_used', 'no_of_comments', 'average_rating', 'img', 'active', 'created_by', 'updated_by',
    ];

    /**
     * @return HasMany
     */
    public function question_details(){
        return $this->hasMany('App\QuestionDetail', 'question_id');
    }

    /**
     * @return HasOne
     */
    public function question_answer(){
        return $this->hasOne('App\QuestionAnswer', 'question_id');
    }

    /**
     * @return HasMany
     */
    public function question_tag(){
        return $this->hasMany('App\QuestionCategoryTag', 'question_id');
    }
}
