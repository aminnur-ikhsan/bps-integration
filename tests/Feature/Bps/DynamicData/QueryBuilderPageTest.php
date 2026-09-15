<?php

namespace Tests\Feature\Bps\DynamicData;

use App\Models\User;
use Tests\TestCase;

class QueryBuilderPageTest extends TestCase
{
    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('bps.dynamic-data.query-builder'))->assertRedirect(route('login'));
    }

    public function test_the_page_is_reachable_from_the_sidebar(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('dashboard'))->assertSee('Data Dinamis');

        $this->get(route('bps.dynamic-data.query-builder'))
            ->assertOk()
            ->assertSee('Data Dinamis');
    }
}
