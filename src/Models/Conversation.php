<?php

namespace Musonza\Chat\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
     * @return BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(Chat::userModel(), 'mc_conversation_user', 'conversation_id', 'user_id')->withTimestamps();
    }

    /**
     * Return the recent message in a Conversation.
     *
     * @return Message
     */
    public function last_message()
    {
        return $this->hasOne(Message::class)->orderBy('mc_messages.id', 'desc')->with('sender');
    }

    /**
     * Messages in conversation.
     *
     * @return Message
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

    /**
     * Gets the list of conversations.
     *
     * @deprecated This will be deprecated in 4.0
     *
     * @param User   $user     The user
     * @param int    $perPage  The per page
     * @param int    $page     The page
     * @param string $pageName The page name
     *
     * @return Conversations The list.
     */
    public function getList($user, $perPage = 25, $page = 1, $pageName = 'page')
    {
        return $this->getConversationsList($user, $perPage, $page, $pageName);
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

    /**
     * Add user to conversation.
     *
     * @param int $userId
     *
     * @return void
     */
    public function addParticipants($userIds)
    {
        if (is_array($userIds)) {
            foreach ($userIds as $id) {
                $this->users()->attach($id);
            }
        } else {
            $this->users()->attach($userIds);
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
            foreach ($users as $id) {
                $this->users()->detach($id);
            }

            return $this;
        }

        $this->users()->detach($users);

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
     * @param User | int $user
     *
     * @return array
     */
    public function userConversations($user)
    {
        $userId = is_object($user) ? $user->getKey() : $user;

        return $this->join('mc_conversation_user', 'mc_conversation_user.conversation_id', '=', 'mc_conversations.id')
            ->where('mc_conversation_user.user_id', $userId)
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
    public function unReadNotifications($user)
    {
        $notifications = MessageNotification::where([['user_id', '=', $user->getKey()], ['conversation_id', '=', $this->id], ['is_seen', '=', 0]])->get();

        return $notifications;
    }

    /**
     * Gets conversations that are common for a list of users.
     *
     * @param \Illuminate\Database\Eloquent\Collection | array $users ids
     *
     * @return \Illuminate\Database\Eloquent\Collection Conversation
     */
    public function common($users)
    {
        if ($users instanceof \Illuminate\Database\Eloquent\Collection) {
            $users = $users->map(function ($user) {
                return $user->getKey();
            });
        }

        return $this->withCount(['users' => function ($query) use ($users) {
            $query->whereIn(Chat::userModelPrimaryKey(), $users);
        }])->get()->filter(function ($conversation, $key) use ($users) {
            return $conversation->users_count == count($users);
        });
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
     * @param User $user
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
     */
    public function readAll($user)
    {
        return $this->getNotifications($user, true);
    }

    private function getConversationMessages($user, $paginationParams, $deleted)
    {
        $messages = $this->messages()
            ->join('mc_message_notification', 'mc_message_notification.message_id', '=', 'mc_messages.id')
            ->where('mc_message_notification.user_id', $user->getKey());
        $messages = $deleted ? $messages->whereNotNull('mc_message_notification.deleted_at') : $messages->whereNull('mc_message_notification.deleted_at');
        $messages = $messages->orderBy('mc_messages.id', $paginationParams['sorting'])
            ->paginate(
                $paginationParams['perPage'],
                [
                    'mc_message_notification.updated_at as read_at',
                    'mc_message_notification.deleted_at as deleted_at',
                    'mc_message_notification.user_id',
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
                        ->where('mc_message_notification.user_id', $user->getKey())
                        ->whereNull('mc_message_notification.deleted_at');
                },
            ])->where('mc_conversation_user.user_id', $user->getKey());

        if (!is_null($isPrivate)) {
            $paginator = $paginator->where('mc_conversations.private', $isPrivate);
        }

        return $paginator->orderBy('mc_conversations.updated_at', 'DESC')
            ->orderBy('mc_conversations.id', 'DESC')
            ->distinct('mc_conversations.id')
            ->paginate($perPage, ['mc_conversations.*'], $pageName, $page);
    }

    private function notifications($user, $readAll)
    {
        $notifications = MessageNotification::where('user_id', $user->getKey())
            ->where('conversation_id', $this->id);

        if ($readAll) {
            return $notifications->update(['is_seen' => 1]);
        }

        return $notifications->get();
    }

    private function clearConversation($user)
    {
        return MessageNotification::where('user_id', $user->getKey())
            ->where('conversation_id', $this->id)
            ->delete();
    }
}
