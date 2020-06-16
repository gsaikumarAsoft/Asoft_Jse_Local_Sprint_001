<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBrokerClientOrderExecutionReports extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('broker_client_order_execution_reports');
        Schema::create('broker_client_order_execution_reports', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('clordid');
            $table->string('orderID');
            $table->string('text')->nullable();
            $table->string('ordRejRes')->nullable();
            $table->string('status')->nullable();
            $table->string('buyorSell')->nullable();
            $table->string('securitySubType')->nullable();
            $table->string('time')->nullable();
            $table->string('ordType')->nullable();
            $table->string('orderQty')->nullable();
            $table->string('timeInForce')->nullable();
            $table->string('symbol')->nullable();
            $table->string('qTradeacc')->nullable();
            $table->string('price')->nullable();
            $table->string('stopPx')->nullable();
            $table->string('execType')->nullable();
            $table->string('senderSubID')->nullable();
            $table->string('seqNum')->nullable();
            $table->string('sendingTime')->nullable();
            $table->string('messageDate')->nullable();
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
        Schema::dropIfExists('broker_client_order_execution_reports');
    }
}
