<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class QuestionCatalogDetail extends Model
{
    protected $table = 'question_catalog_details';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'question_catalog_id', 'question_id'
    ];

    /**
     * @return HasMany`
     */
    public function question_catalog()
    {
        return $this->belongsTo('App\QuestionCatalog', 'question_catalog_id');
    }

    public function questions()
    {
        return $this->hasMany('APP\Question', 'question_id');
    }
}
