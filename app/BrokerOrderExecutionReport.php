<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BrokerOrderExecutionReport extends Model
{
    protected $guarded = ['id'];
    protected $table = 'broker_client_order_execution_reports';

    protected $fillable = [
        'clordid',
        'orderID',
        'text',
        'ordRejRes',
        'status',
        'buyorSell',
        'securitySubType',
        'time',
        'ordType',
        'orderQty',
        'timeInForce',
        'symbol',
        'qTradeacc',
        'price',
        'stopPx',
        'execType',
        'senderSubID',
        'seqNum',
        'sendingTime',
        'messageDate',
    ];
    public function order()
    {
        return $this->belongsTo(BrokerClientOrder::class);
    }
}
