<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionCategoryTag extends Model
{
    protected $table = 'question_category_tags';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'question_id', 'category_id'
    ];

    /**
     * @return BelongsTo
     */
    public function question(){
        return $this->belongsTo('App\Question', 'question_id');
    }

    /**
     * @return BelongsTo
     */
    public function category(){
        return $this->belongsTo('App\QuestionCategory', 'category_id');
    }
}
