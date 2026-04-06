<?php

namespace Musonza\Chat\Http\Requests;

class DeleteMessage extends BaseRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'participant_id'   => 'required',
            'participant_type' => 'required|string',
        ];
    }
}
