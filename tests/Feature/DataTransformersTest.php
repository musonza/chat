<?php

namespace Musonza\Chat\Tests\Feature;

use Musonza\Chat\Models\Conversation;
use Musonza\Chat\Tests\Helpers\Transformers\TestConversationTransformer;
use Musonza\Chat\Tests\TestCase;

class DataTransformersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('musonza_chat.should_load_routes', true);
    }

    public function test_conversation_without_transformer()
    {
        $conversation               = factory(Conversation::class)->create();
        $responseWithoutTransformer = $this->getJson(route('conversations.show', $conversation->getKey()))
            ->assertStatus(200);

        $this->assertInstanceOf(Conversation::class, $responseWithoutTransformer->getOriginalContent());
    }

    public function test_conversation_with_transformer()
    {
        $conversation = factory(Conversation::class)->create();
        $this->app['config']->set('musonza_chat.transformers.conversation', TestConversationTransformer::class);

        $responseWithTransformer = $this->getJson(route('conversations.show', $conversation->getKey()))
            ->assertStatus(200);

        $this->assertInstanceOf('stdClass', $responseWithTransformer->getOriginalContent());
    }
}
