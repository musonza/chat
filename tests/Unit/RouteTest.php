<?php

namespace Musonza\Chat\Tests;

use Illuminate\Support\Facades\Route;

class RouteTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // Disable route caching and refresh routes
        Route::flushMiddlewareGroups();
        Route::clearResolvedInstances();
    }

    /** @test */
    public function it_can_disable_routes()
    {
        // Disable route loading
        $this->app['config']->set('musonza_chat.should_load_routes', false);
        $this->refreshApplication();

        $response = $this->get('/conversations');
        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_enable_routes()
    {
        // Enable route loading
        $this->app['config']->set('musonza_chat.should_load_routes', true);
        $this->refreshApplication();

        $response = $this->get('/conversations');
        $response->assertStatus(200);
    }
}
