<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class LocalBroker extends Model
{

    protected $guarded = ['id'];

    protected $fillable = [
        'user_id',
        'dma_client_id'

    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function clients()
    {
        return $this->hasMany(BrokerClient::class)->where('status', 'Verified');
    }

    public function settlement()
    {
        return $this->hasMany(BrokerSettlementAccount::class);
    }
    public function trading()
    {
        return $this->hasMany(BrokerTradingAccount::class);
    }
    public function order()
    {
        return $this->hasMany(BrokerClientOrder::class);
    }
    

}
 