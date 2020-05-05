<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BrokerClientOrder extends Model
{
    protected $guarded = ['id'];
    // protected $primaryKey = 'id';
    // protected $casts = ['handling_instructions' => 'array'];

    protected $fillable = [
        'local_broker_id',
        'foreign_broker_id',
        'handling_instructions',
        'order_quantity',
        'order_type',
        'order_status',
        'order_date',
        'currency',
        'symbol',
        'price',
        'quantity',
        'country',
        'side',
        'status_time',
        'client_order_number',
        'market_order_number',
        'stop_price',
        'expiration_date',
        'time_in_force',
        'value',
        

    ];
}
