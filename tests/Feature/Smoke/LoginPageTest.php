<?php

namespace Tests\Feature\Smoke;

use Tests\TestCase;

class LoginPageTest extends TestCase
{
    public function test_login_page_can_be_opened(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('Pierakstīties');
        $response->assertSee('Demo admins');
        $response->assertSee('Demo darbinieks');
    }
}
