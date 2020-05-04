<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMissingFieldsForExecutionReportToBrokerClientOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('broker_client_orders', function (Blueprint $table) {
            $table->bigInteger('execID');                                                          
			$table->string('text');
			$table->string('ordRejRes');
			$table->string('securitySubType');
			$table->string('time');
			$table->string('qTradeacc');
			$table->string('execType');
			$table->string('senderSubID');
			$table->string('seqNum');
			$table->string('sendingTime');
			$table->string('messageDate');
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
