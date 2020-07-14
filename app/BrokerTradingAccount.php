<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BrokerTradingAccount extends Model
{


    protected $guarded = ['id'];
    protected $fillable = [
        'local_broker_id',
        'foreign_broker_id',
        'umir',
        'target_comp_id',
        'sender_comp_id',
        'socket',
        'port',
        'trading_account_number',
        'broker_settlement_account_id',
        'status',
        'hash'
    ];
    public function local_broker()
    {
        return $this->belongsTo(LocalBroker::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function foreign_broker()
    {
        return $this->belongsTo(ForeignBroker::class);
    }
    public function settlement_account()
    {
        return $this->belongsTo(BrokerSettlementAccount::class, 'broker_settlement_account_id');
        // return $this->belongsTo(User::class, 'user_id');
    }
}
