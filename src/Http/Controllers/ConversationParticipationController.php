<?php

namespace Musonza\Chat\Http\Controllers;

use Chat;
use Musonza\Chat\Http\Requests\StoreParticipation;
use Musonza\Chat\Http\Requests\UpdateParticipation;
use Musonza\Chat\Models\Participation;
use Symfony\Component\HttpFoundation\Response;

class ConversationParticipationController extends Controller
{
    public function store(StoreParticipation $request, $conversationId)
    {
        $conversation = Chat::conversations()->getById($conversationId);
        Chat::conversation($conversation)->addParticipants($request->participants());

        return response($conversation->participants);
    }

<<<<<<< HEAD
    public function index($conversationId)
    {
        $conversation = Chat::conversations()->getById($conversationId);

        return response($conversation->participants);
    }

    public function show($conversationId, $participationId)
    {
        $participation = Participation::find($participationId);

        return response($participation);
    }

    public function update(UpdateParticipation $request, $conversationId, $participationId)
    {
        $participation = Participation::find($participationId);

        if ($participation->conversation_id != $conversationId) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $participation->update($request->validated());

        return response($participation);
    }

=======
>>>>>>> f6b36792400fb3f9350c0b0a8816ef0b31b29638
    public function destroy($conversationId, $participationId)
    {
        $conversation = Chat::conversations()->getById($conversationId);
        $participation = Participation::find($participationId);
        $conversation = Chat::conversation($conversation)->removeParticipants([$participation->messageable]);

        return response($conversation->participants);
    }
}
