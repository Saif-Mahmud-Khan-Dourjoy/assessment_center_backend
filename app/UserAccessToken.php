<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserAccessToken extends Model
{
    protected  $table = 'oauth_access_tokens';
    protected $fillable = [
        'user_id', 'name', 'revoked', 'expires_at'
    ];
}
