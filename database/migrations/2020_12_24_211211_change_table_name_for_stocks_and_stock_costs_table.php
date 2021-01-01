<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeTableNameForStocksAndStockCostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stocks', function($table)
        {
            $table->dropUnique(['shop_id','platform_item_id','platform_variation_id']);
        });

        Schema::rename('stocks', 'shopee_stocks');
        Schema::table('shopee_stocks', function($table)
        {
            $table->unique(['shop_id','platform_item_id','platform_variation_id'],'shopee_stocks_item_unique');
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
