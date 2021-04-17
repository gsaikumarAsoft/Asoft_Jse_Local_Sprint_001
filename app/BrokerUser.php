<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BrokerUser extends Model
{
    protected $guarded = ['id'];
    protected $fillable = [
        'user_id',
        'dma_broker_id',
        'broker_trading_account_id',
    ];

    public function local_broker()
    {
        return $this->belongsTo(LocalBroker::class);
    }
    public function broker_user()
    {
        return $this->belongsTo(BrokerUser::class);
    }


    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function permission()
    {
        return $this->hasMany(BrokerUserPermission::class);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function trading_account(){
        return $this->hasMany(BrokerTradingAccount::class);
    }
    
}
 