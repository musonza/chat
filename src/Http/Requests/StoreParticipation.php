<?php

namespace Musonza\Chat\Http\Requests;

class StoreParticipation extends BaseRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'participants'        => 'required|array',
            'participants.*.id'   => 'required',
            'participants.*.type' => 'required|string',
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
