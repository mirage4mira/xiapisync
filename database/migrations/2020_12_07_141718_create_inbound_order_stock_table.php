<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInboundOrderStockTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inbound_order_stock', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('inbound_order_id')->nullable(false);
            $table->unsignedInteger('stock_id')->nullable(false);
            $table->unsignedInteger('quantity')->nullable(false);
            $table->float('cost',10,2)->nullable(false);
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
        Schema::dropIfExists('inbounds');
    }
}
