<?php

namespace Musonza\Chat\Http\Controllers;

use Chat;
use Musonza\Chat\Http\Requests\StoreParticipation;
use Musonza\Chat\Models\Participation;

class ConversationParticipationController extends Controller
{
    public function store(StoreParticipation $request, $conversationId)
    {
        $conversation = Chat::conversations()->getById($conversationId);
        Chat::conversation($conversation)->addParticipants($request->participants());

        return response($conversation->participants);
    }

    public function destroy($conversationId, $participationId)
    {
        $conversation = Chat::conversations()->getById($conversationId);
        $participation = Participation::find($participationId);
        $conversation = Chat::conversation($conversation)->removeParticipants([$participation->messageable]);

        return response($conversation->participants);
    }
}
