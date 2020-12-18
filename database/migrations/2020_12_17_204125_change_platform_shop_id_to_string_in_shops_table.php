<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangePlatformShopIdToStringInShopsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shops', function (Blueprint $table) {
            // $table->string('platform_shop_id')->nullable(false)->change();
            DB::statement('ALTER TABLE shops MODIFY platform_shop_id VARCHAR(200) NOT NULL');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shops', function (Blueprint $table) {
            DB::statement('ALTER TABLE shops MODIFY platform_shop_id INTEGER(20) NOT NULL');
        });
    }
}
