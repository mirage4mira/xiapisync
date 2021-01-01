<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeChangesToLazadaStocksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lazada_stocks', function (Blueprint $table) {
            $table->string('platform_item_id')->nullable(false)->change();
            $table->string('platform_sku_id')->nullable(false)->change();
            $table->string('platform_seller_sku')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lazada_stocks', function (Blueprint $table) {
            //
        });
    }
}
