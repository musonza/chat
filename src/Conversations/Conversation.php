<?php

namespace Musonza\Chat\Conversations;

use Musonza\Chat\Chat;
use Musonza\Chat\Messages\Message;
use Musonza\Chat\Model;
use Musonza\Chat\Notifications\MessageNotification;

class Conversation extends Model
{
    protected $table = 'mc_conversations';

    protected $fillable = ['data'];

    protected $casts = [
        'data' => 'array',
    ];

    /**
     * Conversation participants.
     *
     * @return User
     */
    public function users()
    {
        return $this->belongsToMany(Chat::userModel(), 'mc_conversation_user')->withTimestamps();
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
     * @param User   $user
     * @param int    $perPage
     * @param int    $page
     * @param string $sorting
     * @param array  $columns
     * @param string $pageName
     *
     * @return Message
     */
    public function getMessages($user, $perPage = 25, $page = 1, $sorting = 'asc', $columns = ['*'], $pageName = 'page')
    {
        if (Chat::laravelNotifications()) {
            return $this->messages()
                ->join('notifications', 'notifications.data->message_id', '=', 'mc_messages.id')
                ->where('notifications.notifiable_id', $user->id)
                ->orderBy('mc_messages.id', $sorting)
                ->paginate(
                    $perPage,
                    [
                        'notifications.read_at', 'notifications.notifiable_id', 'notifications.id as notification_id',
                        'mc_messages.*',
                    ],
                    $pageName,
                    $page
                );
        }

        return $this->messages()
            ->join('message_notification', 'message_notification.message_id', '=', 'mc_messages.id')
            ->where('message_notification.user_id', $user->id)
            ->orderBy('mc_messages.id', $sorting)
            ->paginate(
                $perPage,
                [
                    'message_notification.updated_at as read_at', 'message_notification.user_id', 'message_notification.id as notification_id',
                    'mc_messages.*',
                ],
                $pageName,
                $page
            );
    }

    /**
     * Gets the list of conversations.
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
        if (Chat::laravelNotifications()) {
            return $this->join('mc_conversation_user', 'mc_conversation_user.conversation_id', '=', 'mc_conversations.id')
                ->with([
                    'last_message' => function ($query) {
                        $query->join('notifications', 'notifications.data->message_id', '=', 'mc_messages.id')
                            ->select('notifications.*', 'mc_messages.*');
                    },
                ])
                ->where('mc_conversation_user.user_id', $user->id)
                ->orderBy('mc_conversations.updated_at', 'DESC')
                ->distinct('mc_conversations.id')
                ->paginate($perPage, ['mc_conversations.*'], $pageName, $page);
        }

        return $this->join('mc_conversation_user', 'mc_conversation_user.conversation_id', '=', 'mc_conversations.id')
            ->with([
                'last_message' => function ($query) {
                    $query->join('message_notification', 'message_notification.message_id', '=', 'mc_messages.id')
                        ->select('message_notification.*', 'mc_messages.*');
                },
            ])            ->where('mc_conversation_user.user_id', $user->id)
            ->orderBy('mc_conversations.updated_at', 'DESC')
            ->distinct('mc_conversations.id')
            ->paginate($perPage, ['mc_conversations.*'], $pageName, $page);
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

        if ($this->users->count() > 2) {
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
    public function start($participants)
    {
        $conversation = $this->create();

        if ($participants) {
            $conversation->addParticipants($participants);
        }

        return $conversation;
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
        $userId = is_object($user) ? $user->id : $user;

        return $this->join('mc_conversation_user', 'mc_conversation_user.conversation_id', '=', 'mc_conversations.id')
            ->where('mc_conversation_user.user_id', $userId)
            ->where('private', true)
            ->pluck('mc_conversations.id');
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
                return $user->id;
            });
        }

        return $this->withCount(['users' => function ($query) use ($users) {
            $query->whereIn('id', $users);
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
        if (Chat::laravelNotifications()) {
            return $user->notifications->filter(function ($item) use ($user) {
                return $item->type == 'Musonza\Chat\Notifications\MessageSent' &&
                    $item->data['conversation_id'] == $this->id &&
                    $item->notifiable_id == $user->id;
            });
        }

        $notifications = MessageNotification::where('user_id', $user->id)
            ->where('conversation_id', $this->id);

            if ($readAll){
                $notifications->update(['is_seen' => 1]);
            } else {
                return $notifications->get();
            }
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
        if (Chat::laravelNotifications()) {
            return $user->notifications()
                ->where('data->conversation_id', $this->id)
                ->delete();
        }

        return MessageNotification::where('user_id', $user->id)
            ->where('conversation_id', $this->id)
            ->delete();
    }

    /**
     * Marks all the messages in a conversation as read.
     *
     * @param $user
     */
    public function readAll($user)
    {
        $this->getNotifications($user, true);
    }
}
