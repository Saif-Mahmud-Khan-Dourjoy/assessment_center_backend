<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Broadcast extends Model
{
    protected $table = 'broadcasts';

    protected $fillable=[
      'title', 'body','type', 'group', 'broadcast_to', 'broadcast_by', 'institute_id',
    ];
}
