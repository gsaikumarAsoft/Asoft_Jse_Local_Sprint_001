<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBrokerClientIdToBrokerClientOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('broker_client_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('broker_client_id');
            $table->foreign('broker_client_id')->references('id')->on('broker_clients')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('broker_client_orders', function (Blueprint $table) {
            //
        });
    }
}
