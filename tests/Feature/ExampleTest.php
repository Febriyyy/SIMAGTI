<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_halaman_awal_mengarah_ke_login_untuk_tamu(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }
}
