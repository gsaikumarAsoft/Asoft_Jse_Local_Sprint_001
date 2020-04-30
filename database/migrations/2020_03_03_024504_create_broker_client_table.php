<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBrokerClientTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('broker_clients', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('local_broker_id')->nullable();
            // $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('orders_limit');
            $table->string('open_orders');
            $table->string('status');
            $table->string('jcsd');
            $table->foreign('local_broker_id')->references('id')->on('local_brokers')->onDelete('cascade');
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
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
        Schema::dropIfExists('broker_clients');
    }
}
