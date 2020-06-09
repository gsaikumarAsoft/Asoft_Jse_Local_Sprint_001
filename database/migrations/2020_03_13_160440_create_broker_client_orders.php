<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBrokerClientOrders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('broker_client_orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('local_broker_id')->unsigned();
            $table->integer('foreign_broker_id')->unsigned();
            $table->text('handling_instructions')->nullable();
            $table->integer('order_quantity')->nullable();
            $table->string('order_type')->nullable();
            $table->string('order_status')->nullable();
            $table->date('order_date');
            $table->string('currency')->nullable();
            $table->string('client_order_number')->nullable();
            $table->string('market_order_number')->nullable();
            $table->string('stop_price')->nullable();
            $table->string('side')->nullable();
            $table->string('value')->nullable();
            $table->string('quantity')->nullable();
            $table->string('expiration_date')->nullable();
            $table->string('time_in_force')->nullable();
            $table->string('symbol')->nullable();
            $table->integer('price')->nullable();
            $table->string('country')->nullable();
            $table->string('status_time')->nullable();
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
        Schema::dropIfExists('broker_client_orders');
    }
}
