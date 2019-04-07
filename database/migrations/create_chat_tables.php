<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatTables extends Migration
{
    protected $userModelPrimaryKey = 'id';
    protected $userModelTable = 'users';

    public function __construct()
    {
        $config = config('musonza_chat');
        $userModel = app($config['user_model']);

        $this->userModelPrimaryKey = $userModel->getKeyName();
        $this->userModelTable = $userModel->getTable();
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mc_conversations', function (Blueprint $table) {
            $table->increments('id');
            $table->boolean('private')->default(true);
            $table->text('data')->nullable();
            $table->timestamps();
        });

        Schema::create('mc_messages', function (Blueprint $table) {
            $table->increments('id');
            $table->text('body');
            $table->integer('conversation_id')->unsigned();
            $table->integer('user_id')->unsigned();
            $table->string('type')->default('text');
            $table->timestamps();

            $table->foreign('user_id')
                ->references($this->userModelPrimaryKey)
                ->on($this->userModelTable)
                ->onDelete('cascade');

            $table->foreign('conversation_id')
                ->references('id')
                ->on('mc_conversations')
                ->onDelete('cascade');
        });

        Schema::create('mc_conversation_user', function (Blueprint $table) {
            $table->integer('user_id')->unsigned();
            $table->integer('conversation_id')->unsigned();
            $table->primary(['user_id', 'conversation_id']);
            $table->timestamps();

            $table->foreign('conversation_id')
                ->references('id')->on('mc_conversations')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references($this->userModelPrimaryKey)
                ->on($this->userModelTable)
                ->onDelete('cascade');
        });

        Schema::create('mc_message_notification', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('message_id')->unsigned();
            $table->integer('conversation_id')->unsigned();
            $table->integer('user_id')->unsigned();
            $table->boolean('is_seen')->default(false);
            $table->boolean('is_sender')->default(false);
            $table->boolean('flagged')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'message_id']);

            $table->foreign('message_id')
                ->references('id')->on('mc_messages')
                ->onDelete('cascade');

            $table->foreign('conversation_id')
                ->references('id')->on('mc_conversations')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references($this->userModelPrimaryKey)
                ->on($this->userModelTable)
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
        Schema::dropIfExists('mc_conversation_user');
        Schema::dropIfExists('mc_message_notification');
        Schema::dropIfExists('mc_messages');
        Schema::dropIfExists('mc_conversations');
    }
}
