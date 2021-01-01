<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStockTableName extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('inbound_order_stock', function (Blueprint $table) {
            $table->enum('stock_table_name',['shopee_stocks','lazada_stocks'])->nullable(false)->after('inbound_order_id');
        });

        Schema::table('stock_costs', function (Blueprint $table) {
            $table->enum('stock_table_name',['shopee_stocks','lazada_stocks'])->nullable(false)->after('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stock_table_name');
    }
}
