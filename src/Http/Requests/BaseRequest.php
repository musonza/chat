<?php

namespace Musonza\Chat\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class BaseRequest extends FormRequest
{
    public function getParticipant()
    {
        $this->validateParticipantType($this->participant_type);

        return app($this->participant_type)->find($this->participant_id);
    }

    /**
     * Validate that the participant_type is an allowed Eloquent model class.
     *
     * @throws ValidationException
     */
    protected function validateParticipantType(string $type): void
    {
        $allowedModels = config('musonza_chat.participant_models', []);

        if (! empty($allowedModels) && ! in_array($type, $allowedModels, true)) {
            throw ValidationException::withMessages([
                'participant_type' => ['The provided participant type is not allowed.'],
            ]);
        }

        if (! class_exists($type) || ! is_a($type, Model::class, true)) {
            throw ValidationException::withMessages([
                'participant_type' => ['The provided participant type must be a valid Eloquent model.'],
            ]);
        }
    }
}
