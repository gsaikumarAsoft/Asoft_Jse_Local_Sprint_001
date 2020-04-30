<?php

use Illuminate\Database\Seeder;
use App\ForeignBroker;

class ForeignBrokerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        ForeignBroker::insert([
            [
                'name' => 'CIBC',
                'email' => 'john@cibc.xcom'
            ]
        ]);
    }
}
