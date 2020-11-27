<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStockTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('shop_id')->nullable(false);
            $table->unsignedInteger('platform_item_id')->nullable(false);
            $table->unsignedInteger('platform_variation_id')->nullable(false)->default(0);
            $table->unsignedInteger('inbound')->nullable(false)->default(0);
            $table->unsignedInteger('safely_stock')->nullable(false)->default(0);
            $table->unsignedInteger('days_to_supply')->nullable(false)->default(0);
            $table->timestamps();

            $table->unique(['platform_item_id', 'platform_variation_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stocks');
    }
}
