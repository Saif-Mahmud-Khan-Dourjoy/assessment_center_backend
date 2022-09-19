<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class QuestionCatalog extends Model
{
    protected $table = 'question_catalogs';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title', 'type', 'institute_id', 'institute', 'total_question', 'status', 'privacy', 'approved_by', 'created_by', 'updated_by'
    ];

    /**
     * @return HasMany
     */
    public function question_catalog_details()
    {
        return $this->hasMany('App\QuestionCatalogDetail', 'question_catalog_id');
    }
}
