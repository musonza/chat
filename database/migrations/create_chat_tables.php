<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Musonza\Chat\ConfigurationManager;

class CreateChatTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(ConfigurationManager::CONVERSATIONS_TABLE, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->boolean('private')->default(true);
            $table->boolean('direct_message')->default(false);
            $table->text('data')->nullable();
            $table->timestamps();
        });

        Schema::create(ConfigurationManager::PARTICIPATION_TABLE, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('conversation_id')->unsigned();
            $table->bigInteger('messageable_id')->unsigned();
            $table->string('messageable_type');
            $table->text('settings')->nullable();
            $table->timestamps();

            $table->unique(['conversation_id', 'messageable_id', 'messageable_type'], 'participation_index');

            $table->foreign('conversation_id')
                ->references('id')
                ->on(ConfigurationManager::CONVERSATIONS_TABLE)
                ->onDelete('cascade');
        });

        Schema::create(ConfigurationManager::MESSAGES_TABLE, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('body');
            $table->bigInteger('conversation_id')->unsigned();
            $table->bigInteger('participation_id')->unsigned()->nullable();
            $table->string('type')->default('text');
            $table->text('data')->nullable();
            $table->timestamps();

            $table->foreign('participation_id')
                ->references('id')
                ->on(ConfigurationManager::PARTICIPATION_TABLE)
                ->onDelete('set null');

            $table->foreign('conversation_id')
                ->references('id')
                ->on(ConfigurationManager::CONVERSATIONS_TABLE)
                ->onDelete('cascade');
        });

        Schema::create(ConfigurationManager::MESSAGE_NOTIFICATIONS_TABLE, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('message_id')->unsigned();
            $table->bigInteger('messageable_id')->unsigned();
            $table->string('messageable_type');
            $table->bigInteger('conversation_id')->unsigned();
            $table->bigInteger('participation_id')->unsigned();
            $table->boolean('is_seen')->default(false);
            $table->boolean('is_sender')->default(false);
            $table->boolean('flagged')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['participation_id', 'message_id'], 'participation_message_index');

            $table->foreign('message_id')
                ->references('id')
                ->on(ConfigurationManager::MESSAGES_TABLE)
                ->onDelete('cascade');

            $table->foreign('conversation_id')
                ->references('id')
                ->on(ConfigurationManager::CONVERSATIONS_TABLE)
                ->onDelete('cascade');

            $table->foreign('participation_id')
                ->references('id')
                ->on(ConfigurationManager::PARTICIPATION_TABLE)
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(ConfigurationManager::MESSAGE_NOTIFICATIONS_TABLE);
        Schema::dropIfExists(ConfigurationManager::MESSAGES_TABLE);
        Schema::dropIfExists(ConfigurationManager::PARTICIPATION_TABLE);
        Schema::dropIfExists(ConfigurationManager::CONVERSATIONS_TABLE);
    }
}
