<?php

namespace Musonza\Chat\Http\Controllers;

use Chat;
use Musonza\Chat\Http\Requests\ClearConversation;
use Musonza\Chat\Http\Requests\DeleteMessage;
use Musonza\Chat\Http\Requests\GetParticipantMessages;
use Musonza\Chat\Http\Requests\GetParticipantMessagesWithCursor;
use Musonza\Chat\Http\Requests\StoreMessage;

class ConversationMessageController extends Controller
{
    protected $messageTransformer;

    public function __construct()
    {
        $this->setUp();
    }

    private function setUp()
    {
        if ($messageTransformer = config('musonza_chat.transformers.message')) {
            $this->messageTransformer = app($messageTransformer);
        }
    }

    private function itemResponse($message)
    {
        if ($this->messageTransformer) {
            return fractal($message, $this->messageTransformer)->respond();
        }

        return response($message);
    }

    public function index(GetParticipantMessages $request, $conversationId)
    {
        $conversation = Chat::conversations()->getById($conversationId);
        $message      = Chat::conversation($conversation)
            ->setParticipant($request->getParticipant())
            ->setPaginationParams($request->getPaginationParams())
            ->getMessages();

        if ($this->messageTransformer) {
            return fractal($message, $this->messageTransformer)->respond();
        }

        return response($message);
    }

    /**
     * Get messages using cursor-based pagination.
     *
     * Cursor pagination is more suitable for real-time chat applications
     * as it avoids duplicate messages when new messages arrive between page loads.
     */
    public function indexWithCursor(GetParticipantMessagesWithCursor $request, $conversationId)
    {
        $conversation = Chat::conversations()->getById($conversationId);
        $messages     = Chat::conversation($conversation)
            ->setParticipant($request->getParticipant())
            ->setCursorPaginationParams($request->getCursorPaginationParams())
            ->getMessagesWithCursor();

        if ($this->messageTransformer) {
            return fractal($messages, $this->messageTransformer)->respond();
        }

        return response($messages);
    }

    public function show(GetParticipantMessages $request, $conversationId, $messageId)
    {
        $message = Chat::messages()->getById($messageId);

        return $this->itemResponse($message);
    }

    public function store(StoreMessage $request, $conversationId)
    {
        $conversation = Chat::conversations()->getById($conversationId);
        $message      = Chat::message($request->getMessageBody())
            ->from($request->getParticipant())
            ->to($conversation)
            ->send();

        return $this->itemResponse($message);
    }

    public function deleteAll(ClearConversation $request, $conversationId)
    {
        $conversation = Chat::conversations()->getById($conversationId);
        Chat::conversation($conversation)
            ->setParticipant($request->getParticipant())
            ->clear();

        return response('');
    }

    public function destroy(DeleteMessage $request, $conversationId, $messageId)
    {
        $message = Chat::messages()->getById($messageId);
        Chat::message($message)
            ->setParticipant($request->getParticipant())
            ->delete();

        return response('');
    }
}
