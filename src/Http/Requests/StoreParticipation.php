<?php

namespace Musonza\Chat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreParticipation extends FormRequest
{
    public function authorized()
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
            $participantModels[] = app($participant['type'])->find($participant['id']);
        }

        return $participantModels;
    }
}
