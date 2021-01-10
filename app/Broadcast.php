<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Broadcast extends Model
{
    protected $table = 'broadcasts';

    protected $fillable=[
      'title', 'body','type', 'broadcast_group', 'broadcast_to', 'broadcasted_by'
    ];
}
