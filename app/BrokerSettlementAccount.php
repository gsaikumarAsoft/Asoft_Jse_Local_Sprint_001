<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BrokerSettlementAccount extends Model
{


    // protected $guarded = ['id'];

    protected $fillable = [
        'local_broker_id',
        'foreign_broker_id',
        'account',
        'bank_name',
        'filled_orders',
        'account',
        'status',
        'currency',
        'email',
        'account_balance',
        'amount_allocated',
        'settlement_agent_status',
        'foreign_broker_status',

    ];

    // public $timestamps = false;

    public function local_broker()
    {
        return $this->belongsTo(User::class);
    }

    public function foreign_broker()
    {
        return $this->belongsTo(User::class);
    }
}
