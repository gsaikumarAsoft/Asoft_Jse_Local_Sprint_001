<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BrokerClient extends Model
{

    protected $guarded = ['id'];

    protected $fillable = [
        'local_broker_id',
        'name',
        'email',
        'orders_limit',
        'account_balance',
        'open_orders',
        'filled_orders',
        'jcsd',
        'status'
    ];

    public function local_broker()
    {
        return $this->belongsTo(LocalBroker::class);
    }
    public function permission()
    {
        return $this->hasMany(BrokerClientPermission::class);
    }
    public function order()
    {
        return $this->hasMany(BrokerClientOrder::class);
    }
}
