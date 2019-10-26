<?php

namespace Musonza\Chat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Musonza\Chat\BaseModel;
use Musonza\Chat\Chat;

class Conversation extends BaseModel
{
    protected $table = 'mc_conversations';
    protected $fillable = ['data'];
    protected $casts = [
        'data' => 'array',
    ];

    /**
     * Conversation participants.
     *
     * @return HasMany
     */
    public function users()
    {
        return $this->hasMany(ConversationUser::class);
    }

    /**
     * Return the recent message in a Conversation.
     *
     * @return HasOne
     */
    public function last_message()
    {
        return $this->hasOne(Message::class)->orderBy('mc_messages.id', 'desc')->with('sender');
    }

    /**
     * Messages in conversation.
     *
     * @return HasMany
     */
    public function messages()
    {
        return $this->hasMany(Message::class, 'conversation_id')->with('sender');
    }

    /**
     * Get messages for a conversation.
     *
     * @param User  $user
     * @param array $paginationParams
     * @param bool  $deleted
     *
     * @return Message
     */
    public function getMessages($user, $paginationParams, $deleted = false)
    {
        return $this->getConversationMessages($user, $paginationParams, $deleted);
    }

    public function getUserConversations($user, array $options)
    {
        return $this->getConversationsList(
            $user,
            $options['perPage'],
            $options['page'],
            $options['pageName'],
            $options['isPrivate']
        );
    }

    public function participantFromSender(Model $sender)
    {
        return $this->users()->where([
            'conversation_id' => $this->getKey(),
            'messageable_id' => $sender->getKey(),
            'messageable_type' => get_class($sender),
        ])->first();
    }

    /**
     * Add user to conversation.
     *
     * @param $users
     *
     * @return Conversation
     */
    public function addParticipants($users): self
    {
        foreach ($users as $user) {
            $user->joinConversation($this->id);
        }

        if (Chat::makeThreeOrMoreUsersPublic() && $this->fresh()->users->count() > 2) {
            $this->private = false;
            $this->save();
        }

        return $this;
    }

    /**
     * Remove user from conversation.
     *
     * @param  $users
     *
     * @return Conversation
     */
    public function removeUsers($users)
    {
        if (is_array($users)) {
            foreach ($users as $user) {
                $user->leaveConversation($this->id);
            }

            return $this;
        }

        $users->leaveConversation($this->id);

        return $this;
    }

    /**
     * Starts a new conversation.
     *
     * @param array $participants users
     *
     * @return Conversation
     */
    public function start($participants, $data = [])
    {
        /** @var Conversation $conversation */
        $conversation = $this->create(['data' => $data]);

        if ($participants) {
            $conversation->addParticipants($participants);
        }

        return $conversation;
    }

    /**
     * Sets conversation as public or private.
     *
     * @param bool $isPrivate
     *
     * @return Conversation
     */
    public function makePrivate($isPrivate = true)
    {
        $this->private = $isPrivate;
        $this->save();

        return $this;
    }

    /**
     * Get number of users in a conversation.
     *
     * @return int
     */
    public function userCount()
    {
        return $this->count();
    }

    /**
     * Gets conversations for a specific user.
     *
     * @param Model $user
     *
     * @return array
     */
    public function userConversations(Model $user)
    {
        return $this->join('mc_conversation_user', 'mc_conversation_user.conversation_id', '=', 'mc_conversations.id')
            ->where('mc_conversation_user.messageable_id', $user->getKey())
            ->where('mc_conversation_user.messageable_type', get_class($user))
            ->where('private', true)
            ->pluck('mc_conversations.id');
    }

    /**
     * Get unread notifications.
     *
     * @param User $user
     *
     * @return void
     */
    public function unReadNotifications(Model $user)
    {
        $notifications = MessageNotification::where([
            ['messageable_id', '=', $user->getKey()],
            ['messageable_type', '=', get_class($user)],
            ['conversation_id', '=', $this->id],
            ['is_seen', '=', 0],
        ])->get();

        return $notifications;
    }

    /**
     * Gets the notifications.
     *
     * @param User $user The user
     *
     * @return Notifications The notifications.
     */
    public function getNotifications($user, $readAll = false)
    {
        return $this->notifications($user, $readAll);
    }

    /**
     * Clears user conversation.
     *
     * @param $user
     *
     * @return
     */
    public function clear($user)
    {
        return $this->clearConversation($user);
    }

    /**
     * Marks all the messages in a conversation as read.
     *
     * @param $user
     *
     * @return Notifications
     */
    public function readAll($user)
    {
        return $this->getNotifications($user, true);
    }

    private function getConversationMessages($user, $paginationParams, $deleted)
    {
        $messages = $this->messages()
            ->join('mc_message_notification', 'mc_message_notification.message_id', '=', 'mc_messages.id')
            ->where('mc_message_notification.messageable_type', get_class($user))
            ->where('mc_message_notification.messageable_id', $user->getKey());
        $messages = $deleted ? $messages->whereNotNull('mc_message_notification.deleted_at') : $messages->whereNull('mc_message_notification.deleted_at');
        $messages = $messages->orderBy('mc_messages.id', $paginationParams['sorting'])
            ->paginate(
                $paginationParams['perPage'],
                [
                    'mc_message_notification.updated_at as read_at',
                    'mc_message_notification.deleted_at as deleted_at',
                    'mc_message_notification.messageable_id',
                    'mc_message_notification.id as notification_id',
                    'mc_messages.*',
                ],
                $paginationParams['pageName'],
                $paginationParams['page']
            );

        return $messages;
    }

    private function getConversationsList($user, $perPage, $page, $pageName, $isPrivate = null)
    {
        $paginator = $this->join('mc_conversation_user', 'mc_conversation_user.conversation_id', '=', 'mc_conversations.id')
            ->with([
                'last_message' => function ($query) use ($user) {
                    $query->join('mc_message_notification', 'mc_message_notification.message_id', '=', 'mc_messages.id')
                        ->select('mc_message_notification.*', 'mc_messages.*')
                        ->where('mc_message_notification.messageable_id', $user->getKey())
                        ->where('mc_message_notification.messageable_type', get_class($user))
                        ->whereNull('mc_message_notification.deleted_at');
                },
            ])
            ->where('mc_conversation_user.messageable_id', $user->getKey())
            ->where('mc_conversation_user.messageable_type', get_class($user));

        if (!is_null($isPrivate)) {
            $paginator = $paginator->where('mc_conversations.private', $isPrivate);
        }

        return $paginator->orderBy('mc_conversations.updated_at', 'DESC')
            ->orderBy('mc_conversations.id', 'DESC')
            ->distinct('mc_conversations.id')
            ->paginate($perPage, ['mc_conversations.*'], $pageName, $page);
    }

    private function notifications(Model $user, $readAll)
    {
        $notifications = MessageNotification::where('messageable_id', $user->getKey())
            ->where('mc_message_notification.messageable_type', get_class($user))
            ->where('conversation_id', $this->id);

        if ($readAll) {
            return $notifications->update(['is_seen' => 1]);
        }

        return $notifications->get();
    }

    private function clearConversation($user)
    {
        return MessageNotification::where('messageable_id', $user->getKey())
            ->where('mc_message_notification.messageable_type', get_class($user))
            ->where('conversation_id', $this->id)
            ->delete();
    }
}
