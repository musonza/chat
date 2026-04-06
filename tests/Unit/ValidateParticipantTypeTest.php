<?php

namespace Musonza\Chat\Tests\Unit;

use Illuminate\Validation\ValidationException;
use Musonza\Chat\Http\Requests\BaseRequest;
use Musonza\Chat\Tests\Helpers\Models\Bot;
use Musonza\Chat\Tests\Helpers\Models\Client;
use Musonza\Chat\Tests\Helpers\Models\User;
use Musonza\Chat\Tests\TestCase;

class ValidateParticipantTypeTest extends TestCase
{
    /** @test */
    public function it_allows_valid_eloquent_model_class()
    {
        $request = $this->makeRequest(User::class);

        $this->invokeValidateParticipantType($request, User::class);

        $this->assertTrue(true);
    }

    /** @test */
    public function it_rejects_nonexistent_class()
    {
        $this->expectException(ValidationException::class);

        $request = $this->makeRequest('App\\Models\\NonExistent');

        $this->invokeValidateParticipantType($request, 'App\\Models\\NonExistent');
    }

    /** @test */
    public function it_rejects_non_model_class()
    {
        $this->expectException(ValidationException::class);

        $request = $this->makeRequest(\stdClass::class);

        $this->invokeValidateParticipantType($request, \stdClass::class);
    }

    /** @test */
    public function it_rejects_class_not_in_whitelist()
    {
        config(['musonza_chat.participant_models' => [User::class]]);

        $this->expectException(ValidationException::class);

        $request = $this->makeRequest(Client::class);

        $this->invokeValidateParticipantType($request, Client::class);
    }

    /** @test */
    public function it_allows_class_in_whitelist()
    {
        config(['musonza_chat.participant_models' => [User::class, Client::class]]);

        $request = $this->makeRequest(User::class);

        $this->invokeValidateParticipantType($request, User::class);

        $this->assertTrue(true);
    }

    /** @test */
    public function it_allows_any_model_when_whitelist_is_empty()
    {
        config(['musonza_chat.participant_models' => []]);

        $request = $this->makeRequest(Bot::class);

        $this->invokeValidateParticipantType($request, Bot::class);

        $this->assertTrue(true);
    }

    /** @test */
    public function it_rejects_nonexistent_class_even_with_empty_whitelist()
    {
        config(['musonza_chat.participant_models' => []]);

        $this->expectException(ValidationException::class);

        $request = $this->makeRequest('App\\Models\\Fake');

        $this->invokeValidateParticipantType($request, 'App\\Models\\Fake');
    }

    /** @test */
    public function it_returns_correct_validation_message_for_disallowed_type()
    {
        config(['musonza_chat.participant_models' => [User::class]]);

        $request = $this->makeRequest(Client::class);

        try {
            $this->invokeValidateParticipantType($request, Client::class);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('participant_type', $e->errors());
            $this->assertEquals('The provided participant type is not allowed.', $e->errors()['participant_type'][0]);
        }
    }

    /** @test */
    public function it_returns_correct_validation_message_for_invalid_model()
    {
        $request = $this->makeRequest(\stdClass::class);

        try {
            $this->invokeValidateParticipantType($request, \stdClass::class);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('participant_type', $e->errors());
            $this->assertEquals(
                'The provided participant type must be a valid Eloquent model.',
                $e->errors()['participant_type'][0]
            );
        }
    }

    private function makeRequest(string $participantType): BaseRequest
    {
        $request                   = new BaseRequest();
        $request->participant_type = $participantType;

        return $request;
    }

    private function invokeValidateParticipantType(BaseRequest $request, string $type): void
    {
        $method = new \ReflectionMethod(BaseRequest::class, 'validateParticipantType');
        $method->setAccessible(true);
        $method->invoke($request, $type);
    }
}
