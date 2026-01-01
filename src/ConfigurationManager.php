<?php

namespace Musonza\Chat;

class ConfigurationManager
{
    const CONVERSATIONS_TABLE = 'chat_conversations';

    const MESSAGES_TABLE = 'chat_messages';

    const MESSAGE_NOTIFICATIONS_TABLE = 'chat_message_notifications';

    const PARTICIPATION_TABLE = 'chat_participation';

    public static function paginationDefaultParameters()
    {
        $pagination = config('musonza_chat.pagination', []);

        return [
            'page'     => $pagination['page']     ?? 1,
            'perPage'  => $pagination['perPage']  ?? 25,
            'sorting'  => $pagination['sorting']  ?? 'asc',
            'columns'  => $pagination['columns']  ?? ['*'],
            'pageName' => $pagination['pageName'] ?? 'page',
        ];
    }
}
