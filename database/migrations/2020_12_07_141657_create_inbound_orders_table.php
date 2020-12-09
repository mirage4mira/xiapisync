<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInboundOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inbound_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('shop_id')->nullable(false);
            $table->date('payment_date')->nullable(false);
            $table->string('reference')->nullable(false);
            $table->string('supplier_name')->nullable(false);
            $table->unsignedInteger('days_to_supply')->nullable(false);
            $table->boolean('stock_received')->nullable(false)->default(false);
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
        Schema::dropIfExists('inbound_orders');
    }
}
