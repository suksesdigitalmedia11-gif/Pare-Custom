<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pendaftaran publik SENGAJA dimatikan pada PareCustom POS:
 * akun hanya boleh dibuat oleh Owner/Admin lewat panel manajemen user.
 */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_route_does_not_exist(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_public_registration_post_is_rejected(): void
    {
        $this->post('/register', [
            'name' => 'Penyusup',
            'email' => 'penyusup@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();

        $this->assertGuest();
    }
}
