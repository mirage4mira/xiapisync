<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateStockSyncsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stock_syncs', function (Blueprint $table) {
            $table->string('sku_id')->nullable(false)->unique()->change();
            $table->string('seller_sku')->nullable(false)->unique()->change();
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
            $table->string('sku_id')->change();
            $table->string('seller_sku')->change();
        });
    }
}
