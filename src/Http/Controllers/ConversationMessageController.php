<?php

namespace Musonza\Chat\Http\Controllers;

use Chat;
use Musonza\Chat\Http\Requests\GetParticipantMessages;
use Musonza\Chat\Http\Requests\StoreMessage;

class ConversationMessageController extends Controller
{
    public function index(GetParticipantMessages $request, $conversationId)
    {
        $conversation = Chat::conversations()->getById($conversationId);
        $message = Chat::conversation($conversation)
            ->setParticipant($request->getParticipant())
            ->setPaginationParams($request->getPaginationParams())
            ->getMessages();

        return response($message);
    }

    public function store(StoreMessage $request, $conversationId)
    {
        $conversation = Chat::conversations()->getById($conversationId);
        $message = Chat::message($request->getMessageBody())
            ->from($request->getParticipant())
            ->to($conversation)
            ->send();

        return response($message);
    }
}
