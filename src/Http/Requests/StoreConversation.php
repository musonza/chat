<?php

namespace Musonza\Chat\Http\Requests;

class StoreConversation extends BaseRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'participants'        => 'array',
            'participants.*.id'   => 'required',
            'participants.*.type' => 'required|string',
            'data'                => 'array',
        ];
    }

    public function participants()
    {
        $participantModels = [];
        $participants      = $this->input('participants', []);

        foreach ($participants as $participant) {
            $this->validateParticipantType($participant['type']);
            $participantModels[] = app($participant['type'])->find($participant['id']);
        }

        return $participantModels;
    }
}
