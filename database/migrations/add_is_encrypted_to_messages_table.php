<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Musonza\Chat\ConfigurationManager;

class AddIsEncryptedToMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(ConfigurationManager::MESSAGES_TABLE, function (Blueprint $table) {
            $table->boolean('is_encrypted')->default(false)->after('data');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(ConfigurationManager::MESSAGES_TABLE, function (Blueprint $table) {
            $table->dropColumn('is_encrypted');
        });
    }
}
