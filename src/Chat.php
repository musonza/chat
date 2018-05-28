<?php

namespace Musonza\Chat;

use Musonza\Chat\Commanding\CommandBus;
use Musonza\Chat\Conversations\Conversation;
use Musonza\Chat\Messages\Message;
use Musonza\Chat\Messages\SendMessageCommand;
use Musonza\Chat\Notifications\MessageNotification;

class Chat
{
    /**
     * Type of message being sent.
     *
     * @var string
     */
    protected $type = 'text';

    /**
     * Message sender.
     *
     * @var int | User
     */
    protected $from;

    /**
     * Message recipient.
     *
     * @var Conversation id
     */
    protected $to;

    /**
     * Message content.
     *
     * @var string
     */
    protected $body;

    protected $perPage = 25;

    protected $page = 1;

    /**
     * @param Conversation $conversation The conversation
     * @param Message           $message      The message
     * @param CommandBus      $commandBus   The command bus
     * @param MessageNotification      $messageNotification   Notifications
     */
    public function __construct(
        Conversation $conversation,
        Message $message,
        CommandBus $commandBus,
        MessageNotification $messageNotification)
    {
        $this->conversation = $conversation;
        $this->message = $message;
        $this->commandBus = $commandBus;
        $this->messageNotification = $messageNotification;
    }

    /**
     * Creates a new conversation.
     *
     * @param array $participants
     * @param array $data
     *
     * @return Conversation
     */
    public function createConversation(array $participants, array $data = null)
    {
        return $this->conversation->start($participants);
    }

    /**
     * Returns a conversation.
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
     * Returns a message.
     *
     * @param int $messageId
     *
     * @return Message
     */
    public function messageWithId($messageId)
    {
        return $this->message->findOrFail($messageId);
    }

    /**
     * Add user(s) to a conversation.
     *
     * @param Conversation $conversation
     * @param int | array  $userId       / array of user ids or an integer
     *
     * @return Conversation
     */
    public function addParticipants(Conversation $conversation, $userId)
    {
        $conversation->addParticipants($userId);
    }

    /**
     * Set the message.
     *
     * @param Message $message
     *
     * @return $this
     */
    public function messages(Message $message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Set the limit.
     *
     * @param int $limit
     *
     * @return $this
     */
    public function limit($limit)
    {
        $this->perPage = $limit ? $limit : $this->perPage;

        return $this;
    }

    /**
     * Set current page for pagination.
     *
     * @param int $page
     *
     * @return $this
     */
    public function page($page)
    {
        $this->page = $page ? $page : $this->page;

        return $this;
    }

    /**
     * Sets user.
     *
     * @param object $user
     *
     * @return $this
     */
    public function for($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Mark a message as read.
     *
     * @return void
     */
    public function markRead()
    {
        $this->message->markRead($this->user);
    }

    /**
     * Set Sender.
     *
     * @param int $from
     *
     * @return $this
     */
    public function from($from)
    {
        $this->from = is_object($from) ? $from->id : $from;

        return $this;
    }

    /**
     * Set Message type.
     *
     * @param string type
     *
     * @return $this
     */
    public function type(String $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Sets Receiver.
     *
     * @param Conversation $to
     *
     * @return $this
     */
    public function to(Conversation $to)
    {
        $this->to = $to;

        return $this;
    }

    /**
     * Sets message body.
     *
     * @param string $body
     *
     * @return $this
     */
    public function message(String $body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Sends the message.
     *
     * @return void
     */
    public function send()
    {
        if (!$this->from) {
            throw new \Exception('Message sender has not been set');
        }

        if (!$this->body) {
            throw new \Exception('Message body has not been set');
        }

        if (!$this->to) {
            throw new \Exception('Message receiver has not been set');
        }

        $command = new SendMessageCommand($this->to, $this->body, $this->from, $this->type);

        return $this->commandBus->execute($command);
    }

    /**
     * Remove user(s) from a conversation.
     *
     * @param Conversation $conversation
     * @param $users / array of user ids or an integer
     *
     * @return Conversation
     */
    public function removeParticipants($conversation, $users)
    {
        return $conversation->removeUsers($users);
    }

    /**
     * Get Conversations with lastest message.
     *
     * @param object $user
     *
     * @return Illuminate\Pagination\LengthAwarePaginator
     */
    public function get()
    {
        return $this->conversation->getList($this->user, $this->perPage, $this->page, $pageName = 'page');
    }

    public function conversations(Conversation $conversation = null)
    {
        $this->conversation = $conversation ? $conversation : $this->conversation;

        return $this;
    }

    /**
     * Get messages in a conversation.
     *
     * @param int $perPage
     * @param int $page
     *
     * @return Message
     */
    public function getMessages($perPage = null, $page = null)
    {
        return $this->conversation->getMessages($this->user, $perPage, $page);
    }

    /**
     * Get messages by id.
     *
     * @param int $id
     *
     * @return Message
     */
    public function messageById($id)
    {
        return $this->message->findOrFail($id);
    }

    /**
     * Deletes message.
     *
     * @return void
     */
    public function delete()
    {
        $this->message->trash($this->user);
    }

    /**
     * Clears conversation.
     */
    public function clear()
    {
        $this->conversation->clear($this->user);
    }

    /**
     * Mark all messages in Conversation as read.
     *
     * @return void
     */
    public function readAll()
    {
        $this->conversation->readAll($this->user);
    }

    /**
     * Get conversations that users have in common.
     *
     *  @param array | collection $users
     *
     * @return Conversations
     */
    public function commonConversations($users)
    {
        return $this->conversation->common($users);
    }

    /**
     * Get Private Conversation between two users.
     *
     * @param int | User $userOne
     * @param int | User $userTwo
     *
     * @return Conversation
     */
    public function getConversationBetween($userOne, $userTwo)
    {
        $conversation1 = $this->conversation->userConversations($userOne)->toArray();
        $conversation2 = $this->conversation->userConversations($userTwo)->toArray();

        $common_conversations = $this->getConversationsInCommon($conversation1, $conversation2);

        if (!$common_conversations) {
            return;
        }

        return $this->conversation->findOrFail($common_conversations[0]);
    }

    /**
     * Gets the conversations in common.
     *
     * @param array $conversation1 The conversations for user one
     * @param array $conversation2 The conversations for user two
     *
     * @return Conversation The conversations in common.
     */
    private function getConversationsInCommon($conversation1, $conversation2)
    {
        return array_values(array_intersect($conversation1, $conversation2));
    }

    /**
     * Get count for unread messages.
     *
     * @return void
     */
    public function unreadCount()
    {
        return $this->message->unreadCount($this->user);
    }

    /**
     * Get unread notifications
     *
     * @return MessageNotification
     */
    public function unReadNotifications()
    {
        return $this->messageNotification->unReadNotifications($this->user);
    }

    /**
     * Returns the User Model class.
     *
     * @return string
     */
    public static function userModel()
    {
        return config('musonza_chat.user_model');
    }

    public static function broadcasts()
    {
        return config('musonza_chat.broadcasts');
    }

    public static function laravelNotifications()
    {
        return config('musonza_chat.laravel_notifications');
    }
}
