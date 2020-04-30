<?php

use App\BrokerClientOrder;
use Illuminate\Database\Seeder;

class BrokerClientOrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

     
        // for ($x = 0; $x <= 1; $x++) {
        //     $price = rand(5000, 13000);
        //     $order_type = array("BUY", "SELL");
        //     $order_status = array("OPEN", "FILLED");
        //     $order_symbol = array("TGIF","FFNT", "ABCS.WT");
        //     $otype_key = array_rand($order_type);
        //     $osymbol_key = array_rand($order_symbol);
        //     $ostat_key = array_rand($order_status);
        //     // echo "The number is: $x <br>";
        //     BrokerClientOrder::insert([
        //         [
        //             'local_broker_id' => 1,
        //             'foreign_broker_id' => 1,
        //             'handling_instructions' => $x.' - Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor.',
        //             'order_type' => $order_type[$otype_key],
        //             'order_status' => $order_status[$ostat_key],
        //             'order_date' => '2020-02-01',
        //             'order_quantity' => $price,
        //             'currency' => 'USD',
        //             'symbol' => $order_symbol[$osymbol_key],
        //             'price' => $price,
        //             'quantity' => $price,
        //             'country' => 'Jamaica',
        //             'status_time' => '2020-03-12',
        //         ]
        //     ]);
        // }

    }
}
