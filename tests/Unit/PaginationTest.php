<?php

namespace Musonza\Chat\Tests;

use Chat;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class PaginationTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function it_can_set_pagination_params()
    {
        $chat = Chat::conversations()->setPaginationParams([
            'perPage'  => 30,
            'page'     => 3,
            'pageName' => 'test',
            'sorting'  => 'desc',
        ]);

        $this->assertEquals(
            [
                'page'    => 3,
                'perPage' => 30,
                'sorting' => 'desc',
                'columns' => [
                    0 => '*',
                ],
                'pageName' => 'test',
            ],
            $chat->getPaginationParams()
        );
    }
}
