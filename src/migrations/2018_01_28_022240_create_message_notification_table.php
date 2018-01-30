<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateMessageNotificationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mc_message_notification', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('message_id')->unsigned();
            $table->integer('conversation_id')->unsigned();
            $table->integer('user_id')->unsigned();
            $table->boolean('is_seen')->default(false);
            $table->boolean('is_sender')->default(false);
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();

            $table->index(['user_id', 'message_id']);

            $table->foreign('message_id')
                ->references('id')->on('mc_messages')
                ->onDelete('cascade');

            $table->foreign('conversation_id')
                ->references('id')->on('mc_conversations')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')->on('users')
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
        Schema::drop('mc_message_notification');
    }
}
