<?php

namespace Musonza\Chat\Models;

use Chat;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Musonza\Chat\BaseModel;
use Musonza\Chat\ConfigurationManager;
use Musonza\Chat\Eventing\AllParticipantsClearedConversation;
use Musonza\Chat\Eventing\ParticipantsJoined;
use Musonza\Chat\Eventing\ParticipantsLeft;
use Musonza\Chat\Exceptions\DeletingConversationWithParticipantsException;
use Musonza\Chat\Exceptions\DirectMessagingExistsException;
use Musonza\Chat\Exceptions\InvalidConversationListException;
use Musonza\Chat\Exceptions\InvalidDirectMessageNumberOfParticipants;

class Conversation extends BaseModel
{
    protected $table = ConfigurationManager::CONVERSATIONS_TABLE;

    protected $fillable = ['data', 'direct_message'];

    protected $casts = [
        'data'           => 'array',
        'direct_message' => 'boolean',
        'private'        => 'boolean',
    ];

    public function delete()
    {
        if ($this->participants()->count()) {
            throw new DeletingConversationWithParticipantsException;
        }

        return parent::delete();
    }

    /**
     * Conversation participants.
     *
     * @return HasMany
     */
    public function participants()
    {
        return $this->hasMany(Participation::class);
    }

    public function getParticipants()
    {
        return $this->participants()->get()->pluck('messageable');
    }

    /**
     * Return the recent message in a Conversation.
     *
     * @return HasOne
     */
    public function last_message()
    {
        return $this->hasOne(Message::class)
            ->orderBy($this->tablePrefix . 'messages.id', 'desc')
            ->with('participation');
    }

    /**
     * Messages in conversation.
     *
     * @return HasMany
     */
    public function messages()
    {
        return $this->hasMany(Message::class, 'conversation_id'); // ->with('sender');
    }

    /**
     * Get messages for a conversation.
     *
     * @param  array  $paginationParams
     * @param  bool  $deleted
     * @return LengthAwarePaginator|HasMany|Builder
     */
    public function getMessages(Model $participant, $paginationParams, $deleted = false)
    {
        return $this->getConversationMessages($participant, $paginationParams, $deleted);
    }

    /**
     * Get messages for a conversation using cursor-based pagination.
     *
     * Cursor pagination is more suitable for real-time chat applications
     * as it avoids duplicate messages when new messages arrive between page loads.
     *
     * @param  array  $paginationParams
     * @param  bool  $deleted
     * @return CursorPaginator
     */
    public function getMessagesWithCursor(Model $participant, $paginationParams, $deleted = false)
    {
        return $this->getConversationMessagesWithCursor($participant, $paginationParams, $deleted);
    }

    public function getParticipantConversations($participant, array $options)
    {
        return $this->getConversationsList($participant, $options);
    }

    /**
     * Get public conversations without requiring a participant.
     *
     * @throws InvalidConversationListException
     */
    public function getPublicConversations(array $options)
    {
        // Only public conversations can be listed without a participant
        if (isset($options['filters']['private']) && $options['filters']['private'] === true) {
            throw new InvalidConversationListException('Cannot list private conversations without a participant.');
        }

        return $this->getPublicConversationsList($options);
    }

    public function participantFromSender(Model $sender)
    {
        return $this->participants()->where([
            'conversation_id'  => $this->getKey(),
            'messageable_id'   => $sender->getKey(),
            'messageable_type' => $sender->getMorphClass(),
        ])->first();
    }

    /**
     * Add user to conversation.
     */
    public function addParticipants(array $participants): self
    {
        foreach ($participants as $participant) {
            $participant->joinConversation($this);
        }

        event(new ParticipantsJoined($this, $participants));

        return $this;
    }

    /**
     * Remove participant from conversation.
     *
     *
     * @return Conversation
     */
    public function removeParticipant($participants)
    {
        if (is_array($participants)) {
            foreach ($participants as $participant) {
                $participant->leaveConversation($this->getKey());
            }

            event(new ParticipantsLeft($this, $participants));

            return $this;
        }

        $participants->leaveConversation($this->getKey());

        event(new ParticipantsLeft($this, [$participants]));

        return $this;
    }

    /**
     * Starts a new conversation.
     *
     *
     * @throws DirectMessagingExistsException
     * @throws InvalidDirectMessageNumberOfParticipants
     */
    public function start(array $payload): self
    {
        if ($payload['direct_message']) {
            if (count($payload['participants']) > 2) {
                throw new InvalidDirectMessageNumberOfParticipants;
            }

            $this->ensureNoDirectMessagingExist($payload['participants']);
        }

        /** @var Conversation $conversation */
        $conversation = $this->create(['data' => $payload['data'], 'direct_message' => (bool) $payload['direct_message']]);

        if ($payload['participants']) {
            $conversation->addParticipants($payload['participants']);
        }

        return $conversation;
    }

    /**
     * Sets conversation as public or private.
     *
     * @param  bool  $isPrivate
     * @return Conversation
     */
    public function makePrivate($isPrivate = true)
    {
        $this->private = $isPrivate;
        $this->save();

        return $this;
    }

    /**
     * Sets conversation as direct message.
     *
     * @param  bool  $isDirect
     * @return Conversation
     *
     * @throws InvalidDirectMessageNumberOfParticipants
     * @throws DirectMessagingExistsException
     */
    public function makeDirect($isDirect = true)
    {
        if ($this->participants()->count() > 2) {
            throw new InvalidDirectMessageNumberOfParticipants;
        }

        $participants = $this->participants()->get()->pluck('messageable');

        $this->ensureNoDirectMessagingExist($participants);

        $this->direct_message = $isDirect;
        $this->save();

        return $this;
    }

    /**
     * @throws DirectMessagingExistsException
     */
    private function ensureNoDirectMessagingExist($participants)
    {
        /** @var Conversation $common */
        $common = Chat::conversations()->between($participants[0], $participants[1]);

        if (! is_null($common)) {
            throw new DirectMessagingExistsException;
        }
    }

    /**
     * Gets conversations for a specific participant.
     */
    public function participantConversations(Model $participant, bool $isDirectMessage = false): Collection
    {
        $conversations = $participant->participation->pluck('conversation');

        return $isDirectMessage ? $conversations->where('direct_message', 1) : $conversations;
    }

    /**
     * Get unread notifications.
     */
    public function unReadNotifications(Model $participant): Collection
    {
        $notifications = MessageNotification::where([
            ['messageable_id', '=', $participant->getKey()],
            ['messageable_type', '=', $participant->getMorphClass()],
            ['conversation_id', '=', $this->id],
            ['is_seen', '=', 0],
        ])->get();

        return $notifications;
    }

    /**
     * Gets the notifications for the participant.
     *
     * @param  bool  $readAll
     * @return MessageNotification
     */
    public function getNotifications($participant, $readAll = false)
    {
        return $this->notifications($participant, $readAll);
    }

    /**
     * Clears participant conversation.
     */
    public function clear($participant): void
    {
        $this->clearConversation($participant);

        if ($this->unDeletedCount() === 0) {
            event(new AllParticipantsClearedConversation($this));
        }
    }

    /**
     * Marks all the messages in a conversation as read for the participant.
     */
    public function readAll(Model $participant): void
    {
        $this->getNotifications($participant, true);
    }

    /**
     * Get messages in conversation for the specific participant.
     *
     *
     * @return LengthAwarePaginator|HasMany|Builder
     */
    private function getConversationMessages(Model $participant, $paginationParams, $deleted)
    {
        $messages = $this->messages()
            ->join($this->tablePrefix . 'message_notifications', $this->tablePrefix . 'message_notifications.message_id', '=', $this->tablePrefix . 'messages.id')
            ->where($this->tablePrefix . 'message_notifications.messageable_type', $participant->getMorphClass())
            ->where($this->tablePrefix . 'message_notifications.messageable_id', $participant->getKey());
        $messages = $deleted ? $messages->whereNotNull($this->tablePrefix . 'message_notifications.deleted_at') : $messages->whereNull($this->tablePrefix . 'message_notifications.deleted_at');
        $messages = $messages->orderBy($this->tablePrefix . 'messages.id', $paginationParams['sorting'])
            ->paginate(
                $paginationParams['perPage'],
                [
                    $this->tablePrefix . 'message_notifications.updated_at as read_at',
                    $this->tablePrefix . 'message_notifications.deleted_at as deleted_at',
                    $this->tablePrefix . 'message_notifications.messageable_id',
                    $this->tablePrefix . 'message_notifications.id as notification_id',
                    $this->tablePrefix . 'message_notifications.is_seen',
                    $this->tablePrefix . 'message_notifications.is_sender',
                    $this->tablePrefix . 'messages.*',
                ],
                $paginationParams['pageName'],
                $paginationParams['page']
            );

        return $messages;
    }

    /**
     * Get messages in conversation using cursor-based pagination.
     *
     * @return CursorPaginator
     */
    private function getConversationMessagesWithCursor(Model $participant, $paginationParams, $deleted)
    {
        $messages = $this->messages()
            ->join($this->tablePrefix . 'message_notifications', $this->tablePrefix . 'message_notifications.message_id', '=', $this->tablePrefix . 'messages.id')
            ->where($this->tablePrefix . 'message_notifications.messageable_type', $participant->getMorphClass())
            ->where($this->tablePrefix . 'message_notifications.messageable_id', $participant->getKey());
        $messages = $deleted ? $messages->whereNotNull($this->tablePrefix . 'message_notifications.deleted_at') : $messages->whereNull($this->tablePrefix . 'message_notifications.deleted_at');
        $messages = $messages->orderBy($this->tablePrefix . 'messages.id', $paginationParams['sorting'])
            ->cursorPaginate(
                $paginationParams['perPage'],
                [
                    $this->tablePrefix . 'message_notifications.updated_at as read_at',
                    $this->tablePrefix . 'message_notifications.deleted_at as deleted_at',
                    $this->tablePrefix . 'message_notifications.messageable_id',
                    $this->tablePrefix . 'message_notifications.id as notification_id',
                    $this->tablePrefix . 'message_notifications.is_seen',
                    $this->tablePrefix . 'message_notifications.is_sender',
                    $this->tablePrefix . 'messages.*',
                ],
                $paginationParams['cursorName'] ?? 'cursor',
                $paginationParams['cursor']     ?? null
            );

        return $messages;
    }

    /**
     * @return mixed
     */
    private function getConversationsList(Model $participant, $options)
    {
        /** @var Builder $paginator */
        $paginator = $participant->participation()
            ->join($this->tablePrefix . 'conversations as c', $this->tablePrefix . 'participation.conversation_id', '=', 'c.id')
            ->with([
                'conversation.last_message' => function ($query) use ($participant) {
                    $query->join($this->tablePrefix . 'message_notifications', $this->tablePrefix . 'message_notifications.message_id', '=', $this->tablePrefix . 'messages.id')
                        ->select($this->tablePrefix . 'message_notifications.*', $this->tablePrefix . 'messages.*')
                        ->where($this->tablePrefix . 'message_notifications.messageable_id', $participant->getKey())
                        ->where($this->tablePrefix . 'message_notifications.messageable_type', $participant->getMorphClass())
                        ->whereNull($this->tablePrefix . 'message_notifications.deleted_at');
                },
                'conversation.participants.messageable',
            ]);

        if (isset($options['filters']['private'])) {
            $paginator = $paginator->where('c.private', (bool) $options['filters']['private']);
        }

        if (isset($options['filters']['direct_message'])) {
            $paginator = $paginator->where('c.direct_message', (bool) $options['filters']['direct_message']);
        }

        $total = $paginator->distinct('c.id')->toBase()->getCountForPagination();

        $paginator = $paginator
            ->orderBy('c.updated_at', 'DESC')
            ->orderBy('c.id', 'DESC')
            ->distinct('c.updated_at', 'c.id');

        return $paginator->paginate($options['perPage'], [$this->tablePrefix . 'participation.*', 'c.*'], $options['pageName'], $options['page'], $total);
    }

    /**
     * Get public conversations list without requiring a participant.
     *
     * @return mixed
     */
    private function getPublicConversationsList(array $options)
    {
        $query = self::query()
            ->where('private', false)
            ->with([
                'last_message.participation',
                'participants.messageable',
            ]);

        if (isset($options['filters']['direct_message'])) {
            $query = $query->where('direct_message', (bool) $options['filters']['direct_message']);
        }

        $query = $query
            ->orderBy('updated_at', 'DESC')
            ->orderBy('id', 'DESC');

        return $query->paginate($options['perPage'], ['*'], $options['pageName'], $options['page']);
    }

    public function unDeletedCount()
    {
        return MessageNotification::where('conversation_id', $this->getKey())
            ->count();
    }

    private function notifications(Model $participant, $readAll)
    {
        $notifications = MessageNotification::where('messageable_id', $participant->getKey())
            ->where($this->tablePrefix . 'message_notifications.messageable_type', $participant->getMorphClass())
            ->where('conversation_id', $this->id);

        if ($readAll) {
            return $notifications->update(['is_seen' => 1]);
        }

        return $notifications->get();
    }

    private function clearConversation($participant): void
    {
        MessageNotification::where('messageable_id', $participant->getKey())
            ->where($this->tablePrefix . 'message_notifications.messageable_type', $participant->getMorphClass())
            ->where('conversation_id', $this->getKey())
            ->delete();
    }

    public function isDirectMessage(): bool
    {
        return (bool) $this->direct_message;
    }
}
