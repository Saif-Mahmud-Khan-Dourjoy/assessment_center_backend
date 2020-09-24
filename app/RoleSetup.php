<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RoleSetup extends Model
{
    protected $table = 'role_setups';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'contributor_role_id', 'new_register_user_role_id'
    ];
}
