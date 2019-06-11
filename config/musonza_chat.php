<?php

return [
    'user_model' => 'App\User',

    /*
     * If not set, the package will use getKeyName() on the user_model specified above
     */
    'user_model_primary_key' => null,

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
     * Automatically convert conversations with more than two users to public
     */
    'make_three_or_more_users_public' => true,
];
