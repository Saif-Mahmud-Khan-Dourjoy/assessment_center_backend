<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionCategory extends Model
{
    protected $table = 'question_categories';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'parents_id', 'layer', 'description'
    ];

    /**
     * @return HasMany
     */
    public function question_details(){
        return $this->hasMany('App\Question', 'category_id');
    }
}
