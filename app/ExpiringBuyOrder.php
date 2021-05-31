<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ExpiringBuyOrder extends Model
{
    protected $fillable = [
        'foreign_broker_user_id',      
    ];
}
