<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ForeignBroker extends Model
{

    protected $guarded = ['id'];

    protected $fillable = [
        'user_id',
        'dma_client_id'

    ];

    public function settlement_accounts()
    {
        return $this->hasMany('App\BrokerSettlementAccount');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
 