<?php

use App\BrokerSettlementAccount;
// use App\SettlementAccount;

use Illuminate\Database\Seeder;
use Twilio\Rest\Accounts;

class SettlementAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        BrokerSettlementAccount::insert([
        //     [
        //         'local_broker_id' => 1,
        //         'foreign_broker_id' => 1,
        //         'bank_name' => 'RBC',
        //         'account' => 'RBC-10272',
        //         'status' => 'Verified',
        //         'email' => 'RBC@gmail.com',
        //         'account_balance' => '0.00',
        //         'amount_allocated' => '0.00',
                
        //     ],
        //     [
        //         'local_broker_id' => 1,
        //         'foreign_broker_id' => 1,
        //         'bank_name' => 'WF',
        //         'Account' => 'WF-4287',
        //         'status' => 'Verified',
        //         'email' => 'wellsfargo@gmail.com',
        //         'account_balance' => '20000.00',
        //         'amount_allocated' => '3000.00',
                
        //     ]
        ]);
    }
}
