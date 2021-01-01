<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLazadaStocksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lazada_stocks', function (Blueprint $table) {
            $table->id();
            $table->integer('shop_id')->nullable(false);
            $table->integer('platform_item_id')->nullable(false);
            $table->integer('platform_sku_id')->nullable(false);
            $table->integer('platform_seller_sku')->nullable(false);
            $table->integer('safety_stock')->nullable(false)->default(0);
            $table->integer('days_to_supply')->nullable(false)->default(0);
            $table->timestamps();

            $table->unique(['shop_id','platform_item_id','platform_sku_id','platform_seller_sku'],'lazada_stocks_item_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('lazada_stocks');
    }
}
