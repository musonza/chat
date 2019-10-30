<?php

namespace Musonza\Chat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BaseRequest extends FormRequest
{
    public function getParticipant()
    {
        return app($this->participant_type)->find($this->participant_id);
    }
}
