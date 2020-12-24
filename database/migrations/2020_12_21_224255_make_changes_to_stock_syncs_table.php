<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeChangesToStockSyncsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stock_syncs', function (Blueprint $table) {
            $table->dropColumn('platform');
        });
        Schema::table('stock_syncs', function (Blueprint $table) {
            $table->integer('mapped_shop_id')->nullable(false);
            $table->datetime('create_time')->nullable(true)->change();
            $table->unique(['stock_id','mapped_shop_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('stock_syncs', function (Blueprint $table) {
            $table->dropColumn('mapped_shop_id');
            $table->enum('platform',['LAZADA','SHOPEE'])->nullable(false);
            $table->datetime('create_time')->nullable(false)->change();
        });
    }
}
