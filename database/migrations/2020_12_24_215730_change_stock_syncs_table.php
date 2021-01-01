<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeStockSyncsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stock_syncs', function (Blueprint $table) {

            $table->dropUnique(['stock_id','mapped_shop_id']);
            $table->dropColumn('stock_id');
            $table->dropColumn('item_id')->nullable(false);
            $table->dropColumn('sku_id')->nullable(false);
            $table->dropColumn('seller_sku')->nullable(false);
            $table->dropColumn('mapped_shop_id')->nullable(false);
            $table->integer('shopee_stock_id')->nullable(false)->default(0)->after('id');
            $table->integer('lazada_stock_id')->nullable(false)->default(0)->after('shopee_stock_id');

            $table->unique(['shopee_stock_id','lazada_stock_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
