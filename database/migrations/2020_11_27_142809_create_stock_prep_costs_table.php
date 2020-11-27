<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStockPrepCostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stock_prep_costs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('stock_id')->nullable(false);
            $table->decimal('prep_cost',10,2)->nullable(false);
            $table->date('from_date')->nullable(false)->default(date('Y-m-d', 0));
            $table->unique(['stock_id', 'from_date']);
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
        Schema::dropIfExists('stock_prep_costs');
    }
}
