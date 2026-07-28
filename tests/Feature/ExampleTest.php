<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_requires_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }
}
