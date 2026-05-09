<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Musonza\Chat\ConfigurationManager;

class AddArchivedAtToParticipationTable extends Migration
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
        $this->schema()->table(ConfigurationManager::PARTICIPATION_TABLE, function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('settings');
            $table->index(['conversation_id', 'messageable_id', 'messageable_type', 'archived_at'], 'participation_archived_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->schema()->table(ConfigurationManager::PARTICIPATION_TABLE, function (Blueprint $table) {
            $table->dropIndex('participation_archived_index');
            $table->dropColumn('archived_at');
        });
    }
}
