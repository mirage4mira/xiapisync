<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStockSyncsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stock_syncs', function (Blueprint $table) {
            $table->id();
            $table->integer('stock_id')->nullable(false);
            $table->enum('platform',['SHOPEE','LAZADA'])->nullable(false);
            $table->string('item_id')->nullable(false);
            $table->string('sku_id')->nullable(true);
            $table->string('seller_sku')->nullable(true);
            $table->datetime('create_time')->nullable(false);
            $table->boolean('sync')->nullable(false);
            $table->datetime('last_sync_time')->nullable(true);
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
        Schema::dropIfExists('stock_syncs');
    }
}
