<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBrokerSettlementAccount extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('broker_settlement_accounts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('local_broker_id');
            $table->unsignedBigInteger('foreign_broker_id');
            $table->string('bank_name');
            $table->string('account');
            $table->string('email');
            $table->string('hash');
            $table->string('settlement_agent_status');
            $table->string('foreign_broker_status');
            $table->decimal('account_balance', 16, 2);
            $table->decimal('amount_allocated', 16, 2);
            $table->string('status');
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
        Schema::dropIfExists('broker_settlement_accounts');
    }
}
