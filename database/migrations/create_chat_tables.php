<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatTables extends Migration
{
    protected $userModelPrimaryKey = 'id';
    protected $userModelTable = 'users';

    protected $useBigIncrements;

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
            $table->bigIncrements('id');
            $table->boolean('private')->default(true);
            $table->text('data')->nullable();
            $table->timestamps();
        });

        Schema::create('mc_messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('body');
            $table->bigInteger('conversation_id')->unsigned();
            $table->bigInteger('user_id')->unsigned();
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
            $table->bigIncrements('id');
            $table->bigInteger('conversation_id')->unsigned();
            $table->bigInteger('messageable_id')->unsigned();
            $table->string('messageable_type');
            $table->index(['conversation_id', 'messageable_id', 'messageable_type']);
            $table->timestamps();

            $table->foreign('conversation_id')
                ->references('id')->on('mc_conversations')
                ->onDelete('cascade');
        });

        Schema::create('mc_message_notification', function (Blueprint $table) {
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

            $table->index(['participation_id', 'message_id']);

            $table->foreign('message_id')
                ->references('id')->on('mc_messages')
                ->onDelete('cascade');

            $table->foreign('conversation_id')
                ->references('id')->on('mc_conversations')
                ->onDelete('cascade');

            $table->foreign('participation_id')
                ->references('id')->on('mc_conversation_user')
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
