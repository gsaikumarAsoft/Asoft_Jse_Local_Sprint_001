<?php

use App\LocalBroker;
use Illuminate\Database\Seeder;
// use Illuminate\Support\Facades\DB;

class LocalBrokerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        
        LocalBroker::insert([
            // [
            // 'user_id' =>  2,
            // 'dma_client_id' => '12312312',
            // ],
            // [
            // 'name' =>  'Bartia',
            // 'email' => 'broker@barita.com',
            // 'status' => 'Approved',
            // // 'email_verified_at' => now(), //For future use
            // 'password' => bcrypt('password')
            // ],
            // [
            // 'name' =>  'SAGICOR',
            // 'email' => 'broker@sagicor.com',
            // 'status' => 'Approved',
            // // 'email_verified_at' => now(), //For future use
            // 'password' => bcrypt('password')
            // ]
        ]);
    }
}
