<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BrokerUserPermission extends Model
{
    protected $guarded = ['id'];

    protected $fillable = [
        'broker_user_id',
        'permission'
    ];
    
    
}
