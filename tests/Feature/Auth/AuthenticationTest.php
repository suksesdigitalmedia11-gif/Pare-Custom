<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Autentikasi PareCustom POS.
 *
 * Berbeda dari Laravel Breeze stock: redirect setelah login ditentukan oleh
 * kolom `usertype`, dan akun nonaktif (is_active=false) ditolak.
 */
class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_owner_is_redirected_to_owner_dashboard(): void
    {
        $user = User::factory()->create(['usertype' => 'owner', 'is_active' => true]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/owner/dashboard');
    }

    public function test_finance_is_redirected_to_finance_dashboard(): void
    {
        $user = User::factory()->create(['usertype' => 'finance', 'is_active' => true]);

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/finance/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_editor_is_redirected_to_editor_dashboard(): void
    {
        $user = User::factory()->create(['usertype' => 'editor', 'is_active' => true]);

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/editor/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_users_cannot_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create(['usertype' => 'owner', 'is_active' => true]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_users_cannot_authenticate(): void
    {
        $user = User::factory()->create(['usertype' => 'owner', 'is_active' => false]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create(['usertype' => 'owner', 'is_active' => true]);

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect('/');

        $this->assertGuest();
    }
}
