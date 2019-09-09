<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTestTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mc_users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('mc_clients', function (Blueprint $table) {
            $table->increments('client_id');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('mc_bots', function (Blueprint $table) {
            $table->increments('bot_id');
            $table->string('name');
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
        Schema::dropIfExists('mc_users');
        Schema::dropIfExists('mc_clients');
        Schema::dropIfExists('mc_bots');
    }
}
