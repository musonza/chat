<?php

namespace Musonza\Chat;

use Musonza\Chat\Commanding\CommandBus;
use Musonza\Chat\Conversations\Conversation;
use Musonza\Chat\Conversations\ConversationUser;
use Musonza\Chat\Messages\Message;
use Musonza\Chat\Messages\SendMessageCommand;
use Musonza\Chat\Notifications\MessageNotification;

class Chat
{
    public function __construct(
        Conversation $conversation,
        Message $message,
        CommandBus $commandBus
    ) {
        $this->conversation = $conversation;
        $this->message = $message;
        $this->commandBus = $commandBus;
    }

    /**
     * Creates a new conversation.
     *
     * @param array $participants
     *
     * @return Conversation
     */
    public function createConversation(array $participants = null)
    {
        return $this->conversation->start($participants);
    }

    /**
     * Returns a new conversation.
     *
     * @param int $conversationId
     *
     * @return Conversation
     */
    public function conversation($conversationId)
    {
        return $this->conversation->findOrFail($conversationId);
    }

    /**
     * Add user(s) to a conversation.
     *
     * @param int   $conversationId
     * @param mixed $userId         / array of user ids or an integer
     *
     * @return Conversation
     */
    public function addParticipants($conversationId, $userId)
    {
        return $this->conversation($conversationId)->addParticipants($userId);
    }

    /**
     * Sends a message.
     *
     * @param int    $conversationId
     * @param string $body
     * @param int    $senderId
     *
     * @return
     */
    public function send($conversationId, $body, $senderId)
    {
        $conversation = $this->conversation->findOrFail($conversationId);

        $command = new SendMessageCommand($conversation, $body, $senderId);

        $this->commandBus->execute($command);
    }

    /**
     * Remove user(s) from a conversation.
     *
     * @param int   $conversationId
     * @param mixed $userId         / array of user ids or an integer
     *
     * @return Coonversation
     */
    public function removeParticipants($conversationId, $userId)
    {
        return $this->conversation($conversationId)->removeUsers($userId);
    }

    /**
     * Get recent user messages for each conversation.
     *
     * @param int $userId
     *
     * @return Message
     */
    public function conversations($userId)
    {
        $c = ConversationUser::join('messages', 'messages.conversation_id', '=', 'conversation_user.conversation_id')
            ->where('conversation_user.user_id', $userId)
            ->groupBy('messages.conversation_id')
            ->orderBy('messages.id', 'DESC')
            ->get(['messages.*', 'messages.id as message_id', 'conversation_user.*']);

        $messages = [];

        foreach ($c as $user) {
            $recent_message = $user->conversation->messages()->orderBy('id', 'desc')->first()->toArray();

            $notification = MessageNotification::where('user_id', $userId)
                ->where('message_id', $user->id)
                ->get(['message_notification.id',
                    'message_notification.is_seen',
                    'message_notification.is_sender', ]
                );

            $messages[] = array_merge(
                $recent_message, ['notification' => $notification]
            );
        }

        return $messages;
    }

    /**
     * Get messages in a conversation.
     *
     * @param int $userId
     * @param int $conversationId
     * @param int $perPage
     * @param int $page
     *
     * @return Message
     */
    public function messages($userId, $conversationId, $perPage = null, $page = null)
    {
        return $this->conversation($conversationId)->getMessages($userId, $perPage, $page);
    }

    /**
     * Deletes message.
     *
     * @param int $messageId
     * @param int $userId    user id
     *
     * @return void
     */
    public function trash($messageId, $userId)
    {
        return $this->message->trash($messageId, $userId);
    }

    /**
     * clears conversation.
     *
     * @param int $conversationId
     * @param int $userId
     */
    public function clear($conversationId, $userId)
    {
        return $this->conversation->clear($conversationId, $userId);
    }

    public function messageRead($messageId, $userId)
    {
        return $this->message->messageRead($messageId, $userId);
    }

    public function conversationRead($conversationId, $userId)
    {
        $this->conversation->conversationRead($conversationId, $userId);
    }

    public function getConversationBetweenUsers($userOne, $userTwo)
    {
        $conversation1 = $this->conversation->userConversations($userOne)->toArray();

        $conversation2 = $this->conversation->userConversations($userTwo)->toArray();

        $common_conversations = $this->getConversationsInCommon($conversation1, $conversation2);

        if (!$common_conversations) {
            return;
        }

        return $this->conversation->findOrFail($common_conversations[0]);
    }

    private function getConversationsInCommon($conversation1, $conversation2)
    {
        return array_values(array_intersect($conversation1, $conversation2));
    }

    public static function userModel()
    {
        return config('chat.user_model');
    }
}
