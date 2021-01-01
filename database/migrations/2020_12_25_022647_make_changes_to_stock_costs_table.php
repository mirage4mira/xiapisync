<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeChangesToStockCostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stock_costs', function (Blueprint $table) {
            $table->dropUnique(['stock_id','from_date']);
            $table->unique(['stock_table_name','stock_id','from_date']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('stock_costs', function (Blueprint $table) {
            //
        });
    }
}
