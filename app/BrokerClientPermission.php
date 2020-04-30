<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BrokerClientPermission extends Model
{
    protected $guarded = ['id'];

    protected $fillable = [
        'broker_client_id',
        'permission'
    ];
}
