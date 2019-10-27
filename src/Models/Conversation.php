<?php

namespace Musonza\Chat\Models;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Musonza\Chat\BaseModel;
use Musonza\Chat\ConfigurationManager;
use Musonza\Chat\Eventing\ParticipantsJoined;
use Musonza\Chat\Eventing\ParticipantsLeft;
use Musonza\Chat\Exceptions\DirectMessagingExistsException;
use Musonza\Chat\Exceptions\InvalidDirectMessageNumberOfParticipants;

class Conversation extends BaseModel
{
    protected $table = ConfigurationManager::CONVERSATIONS_TABLE;
    protected $fillable = ['data'];
    protected $casts = [
        'data' => 'array',
    ];

    /**
     * Conversation participants.
     *
     * @return HasMany
     */
    public function participants()
    {
        return $this->hasMany(Participation::class);
    }

    /**
     * Return the recent message in a Conversation.
     *
     * @return HasOne
     */
    public function last_message()
    {
        return $this->hasOne(Message::class)
            ->orderBy('mc_messages.id', 'desc')
            ->with('participation');
    }

    /**
     * Messages in conversation.
     *
     * @return HasMany
     */
    public function messages()
    {
        return $this->hasMany(Message::class, 'conversation_id'); //->with('sender');
    }

    /**
     * Get messages for a conversation.
     *
     * @param Model $participant
     * @param array $paginationParams
     * @param bool  $deleted
     *
     * @return LengthAwarePaginator|HasMany|Builder
     */
    public function getMessages(Model $participant, $paginationParams, $deleted = false)
    {
        return $this->getConversationMessages($participant, $paginationParams, $deleted);
    }

    public function getParticipantConversations($participant, array $options)
    {
        return $this->getConversationsList(
            $participant,
            $options['perPage'],
            $options['page'],
            $options['pageName'],
            $options['filters']
        );
    }

    public function participantFromSender(Model $sender)
    {
        return $this->participants()->where([
            'conversation_id'  => $this->getKey(),
            'messageable_id'   => $sender->getKey(),
            'messageable_type' => get_class($sender),
        ])->first();
    }

    /**
     * Add user to conversation.
     *
     * @param $participants
     *
     * @return Conversation
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
     * @param  $participants
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
     * @param array $participants
     * @param array $data
     *
     * @return Conversation
     */
    public function start(array $participants, $data = []): self
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
     * Sets conversation as direct message.
     *
     * @param bool $isDirect
     *
     * @throws InvalidDirectMessageNumberOfParticipants
     * @throws DirectMessagingExistsException
     *
     * @return Conversation
     */
    public function makeDirect($isDirect = true)
    {
        if ($this->participants()->count() > 2) {
            throw new InvalidDirectMessageNumberOfParticipants();
        }

        $this->ensureNoDirectMessagingExist();

        $this->direct_message = $isDirect;
        $this->save();

        return $this;
    }

    private function ensureNoDirectMessagingExist()
    {
        $participants = $this->participants()->get()->pluck('messageable');

        $modelOne = $participants[0];
        $modelTwo = $participants[1];

        $common = \Chat::conversations()->between($modelOne, $modelTwo);

        if (!is_null($common)) {
            throw new DirectMessagingExistsException();
        }
    }

    /**
     * Gets conversations for a specific participant.
     *
     * @param Model $participant
     * @param bool  $isDirectMessage
     *
     * @return Collection
     */
    public function participantConversations(Model $participant, bool $isDirectMessage = false): Collection
    {
        $conversations = $participant->participation->pluck('conversation');

        return $isDirectMessage ? $conversations->where('direct_message', 1) : $conversations;
    }

    /**
     * Get unread notifications.
     *
     * @param Model $participant
     *
     * @return Collection
     */
    public function unReadNotifications(Model $participant): Collection
    {
        $notifications = MessageNotification::where([
            ['messageable_id', '=', $participant->getKey()],
            ['messageable_type', '=', get_class($participant)],
            ['conversation_id', '=', $this->id],
            ['is_seen', '=', 0],
        ])->get();

        return $notifications;
    }

    /**
     * Gets the notifications for the participant.
     *
     * @param  $participant
     * @param bool $readAll
     *
     * @return MessageNotification
     */
    public function getNotifications($participant, $readAll = false)
    {
        return $this->notifications($participant, $readAll);
    }

    /**
     * Clears participant conversation.
     *
     * @param $participant
     *
     * @return void
     */
    public function clear($participant): void
    {
        $this->clearConversation($participant);
    }

    /**
     * Marks all the messages in a conversation as read for the participant.
     *
     * @param Model $participant
     *
     * @return void
     */
    public function readAll(Model $participant): void
    {
        $this->getNotifications($participant, true);
    }

    /**
     * Get messages in conversation for the specific participant.
     *
     * @param Model $participant
     * @param $paginationParams
     * @param $deleted
     *
     * @return LengthAwarePaginator|HasMany|Builder
     */
    private function getConversationMessages(Model $participant, $paginationParams, $deleted)
    {
        $messages = $this->messages()
            ->join('mc_message_notification', 'mc_message_notification.message_id', '=', 'mc_messages.id')
            ->where('mc_message_notification.messageable_type', get_class($participant))
            ->where('mc_message_notification.messageable_id', $participant->getKey());
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

    /**
     * @param Model $participant
     * @param $perPage
     * @param $page
     * @param $pageName
     * @param $filters
     *
     * @return mixed
     */
    private function getConversationsList(Model $participant, $perPage, $page, $pageName, $filters = [])
    {
        $paginator = $this->join('mc_participation', 'mc_participation.conversation_id', '=', 'mc_conversations.id')
            ->with([
                'last_message' => function ($query) use ($participant) {
                    $query->join('mc_message_notification', 'mc_message_notification.message_id', '=', 'mc_messages.id')
                        ->select('mc_message_notification.*', 'mc_messages.*')
                        ->where('mc_message_notification.messageable_id', $participant->getKey())
                        ->where('mc_message_notification.messageable_type', get_class($participant))
                        ->whereNull('mc_message_notification.deleted_at');
                },
            ])
            ->where('mc_participation.messageable_id', $participant->getKey())
            ->where('mc_participation.messageable_type', get_class($participant));

        if (isset($filters['private'])) {
            $paginator = $paginator->where('mc_conversations.private', (bool) $filters['private']);
        }

        return $paginator->orderBy('mc_conversations.updated_at', 'DESC')
            ->orderBy('mc_conversations.id', 'DESC')
            ->distinct('mc_conversations.id')
            ->paginate($perPage, ['mc_conversations.*'], $pageName, $page);
    }

    private function notifications(Model $participant, $readAll)
    {
        $notifications = MessageNotification::where('messageable_id', $participant->getKey())
            ->where('mc_message_notification.messageable_type', get_class($participant))
            ->where('conversation_id', $this->id);

        if ($readAll) {
            return $notifications->update(['is_seen' => 1]);
        }

        return $notifications->get();
    }

    private function clearConversation($participant): void
    {
        MessageNotification::where('messageable_id', $participant->getKey())
            ->where('mc_message_notification.messageable_type', get_class($participant))
            ->where('conversation_id', $this->getKey())
            ->delete();
    }

    public function isDirectMessage()
    {
        return (bool) $this->direct_message;
    }
}
