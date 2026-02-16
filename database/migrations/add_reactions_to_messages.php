<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Musonza\Chat\ConfigurationManager;

class AddReactionsToMessages extends Migration
{
    protected function schema()
    {
        $connection = config('musonza_chat.database_connection');

        return $connection ? Schema::connection($connection) : Schema::getFacadeRoot();
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->schema()->create(ConfigurationManager::REACTIONS_TABLE, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('message_id')->unsigned();
            $table->bigInteger('messageable_id')->unsigned();
            $table->string('messageable_type');
            $table->string('reaction', 50); // emoji or reaction type (e.g., '👍', 'like', 'heart')
            $table->timestamps();

            // Each participant can only have one reaction of each type per message
            $table->unique(
                ['message_id', 'messageable_id', 'messageable_type', 'reaction'],
                'unique_reaction_per_user'
            );

            $table->index(['message_id'], 'reactions_message_index');
            $table->index(['messageable_id', 'messageable_type'], 'reactions_messageable_index');

            $table->foreign('message_id')
                ->references('id')
                ->on(ConfigurationManager::MESSAGES_TABLE)
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
        $this->schema()->dropIfExists(ConfigurationManager::REACTIONS_TABLE);
    }
}
