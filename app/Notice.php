<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Notice extends Model
{
    protected $table  = 'notices';

    protected $fillable=[
        'title','body','created_by', 'updated_by', 'institute_id','status'
    ];
}
