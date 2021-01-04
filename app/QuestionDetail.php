<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionDetail extends Model
{
    protected $table = 'question_details';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'question_id', 'serial_no', 'option', 'description', 'image'
    ];

    /**
     * @return BelongsTo
     */
    public function question(){
        return $this->belongsTo('App\Question', 'question_id');
    }

}
