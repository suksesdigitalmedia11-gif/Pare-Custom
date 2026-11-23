<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create(['usertype' => 'owner', 'is_active' => true]);

        $this->actingAs($user)->get('/profile')->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create(['usertype' => 'owner', 'is_active' => true]);

        $response = $this->actingAs($user)->patch('/profile', [
            'name' => 'Nama Baru',
            'email' => 'nama.baru@example.com',
        ]);

        $response->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('Nama Baru', $user->name);
        $this->assertSame('nama.baru@example.com', $user->email);
    }

    public function test_profile_update_requires_unique_email(): void
    {
        $other = User::factory()->create(['usertype' => 'owner', 'is_active' => true]);
        $user = User::factory()->create(['usertype' => 'owner', 'is_active' => true]);

        $this->actingAs($user)
            ->patch('/profile', ['name' => 'X', 'email' => $other->email])
            ->assertSessionHasErrors('email');
    }
}
