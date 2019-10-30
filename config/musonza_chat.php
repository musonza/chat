<?php

return [
    /*
     * This will allow you to broadcast an event when a message is sent
     * Example:
     * Channel: mc-chat-conversation.2,
     * Event: Musonza\Chat\Eventing\MessageWasSent
     */
    'broadcasts' => false,

    /*
     * The event to fire when a message is sent
     * See Musonza\Chat\Eventing\MessageWasSent if you want to customize.
     */
    'sent_message_event' => 'Musonza\Chat\Eventing\MessageWasSent',

    /*
     * Specify the fields that you want to return each time for the sender.
     * If not set or empty, all the columns for the sender will be returned
     *
     * However, if using multiple Models it's recommended to add getParticipantDetails to each
     * Model you want to control fields output.
     */

    /*
     * Specify the fields that you want to return each time for the sender.
     * If not set or empty, all the columns for the sender will be returned
     */
    'sender_fields_whitelist' => [],

    'routes' => [
        'enabled' => false,
        'path_prefix' => 'chat',
        'middleware' => ['web'],
    ],
];
