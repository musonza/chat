<?php

return [
    'user_model'            => 'App\User',

    /*
     * This will allow you to broadcast an event when a message is sent
     * Example:
     * Channel: private-mc-chat-conversation.2,
     * Event: Musonza\Chat\Messages\MessageWasSent
     */
    'broadcasts'            => false,

    /*
     * If set to true, this will use Laravel notifications table to store each
     * user message notification.
     * Otherwise it will use mc_message_notification table.
     * If your database doesn't support JSON columns you will need to set this to false.
     */
    'laravel_notifications' => true,
];
