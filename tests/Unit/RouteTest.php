<?php

namespace Musonza\Chat\Tests;

use Illuminate\Support\Facades\Route;

class RouteTest extends TestCase
{
    /** @test */
    public function routes_are_registered_when_enabled(): void
    {
        // Routes should be loaded since we set should_load_routes = true in getEnvironmentSetUp
        $this->assertTrue(Route::has('conversations.index'));
        $this->assertTrue(Route::has('conversations.store'));
        $this->assertTrue(Route::has('conversations.show'));
        $this->assertTrue(Route::has('conversations.update'));
        $this->assertTrue(Route::has('conversations.destroy'));
    }

    /** @test */
    public function conversation_routes_return_correct_responses(): void
    {
        $response = $this->get('/chat/conversations');
        $response->assertStatus(200);
    }
}
