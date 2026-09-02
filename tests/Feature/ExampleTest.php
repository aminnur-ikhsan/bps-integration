<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{

    public function test_a_guest_visiting_root_is_redirected_to_login(): void
    {
        $response = $this->get(route('home'));

        $response->assertRedirect(route('login'));
    }
}
