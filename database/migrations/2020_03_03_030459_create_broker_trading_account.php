<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBrokerTradingAccount extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('broker_trading_accounts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('local_broker_id');
            $table->foreign('local_broker_id')->references('id')->on('local_brokers');
            $table->unsignedBigInteger('foreign_broker_id');
            $table->foreign('foreign_broker_id')->references('id')->on('foreign_brokers')->onDelete('cascade');
            $table->unsignedBigInteger('broker_settlement_account_id');
            $table->foreign('broker_settlement_account_id')->references('id')->on('broker_settlement_accounts')->onDelete('cascade');
            $table->string('umir');
            $table->string('trading_account_number')->nullable();
            $table->string('target_comp_id');
            $table->string('sender_comp_id');
            $table->string('socket');
            $table->string('port');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('broker_trading_accounts');
    }
}
