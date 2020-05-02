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
        'account',
        'status',
        'currency',
        'email',
        'account_balance',
        'amount_allocated'
    ];

    public $timestamps = false;
    
    public function local_broker()
    {
        return $this->belongsTo(User::class);
    }

    public function foreign_broker()
    {
        return $this->belongsTo(User::class);
    }

  

}