<?php

namespace Musonza\Chat\Http\Requests;

class StoreMessage extends BaseRequest
{
    public function authorized()
    {
        return true;
    }

    public function rules()
    {
        return [
            'participant_id'   => 'required',
            'participant_type' => 'required|string',
            'message'          => 'required|array',
            'message.body'     => 'required',
        ];
    }

    public function getMessageBody()
    {
        return $this->message['body'];
    }
}
