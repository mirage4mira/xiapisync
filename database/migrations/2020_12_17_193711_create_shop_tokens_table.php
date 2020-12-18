<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShopTokensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shop_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('access_token')->unique();
            $table->string('refresh_token')->unique();
            $table->string('country');
            $table->integer('refresh_expires_in');
            $table->string('account_platform');
            $table->integer('expires_in');
            $table->string('account');
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
        Schema::dropIfExists('shop_tokens');
    }
}
