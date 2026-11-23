<?php

namespace Tests\Feature\Security;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Rate limiting: mitigasi brute force login + penyalahgunaan endpoint.
 * Nilai batas berasal dari config/rate_limit.php (dapat dioverride lewat .env).
 */
class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_named_limiters_are_registered(): void
    {
        $this->assertNotNull(RateLimiter::limiter('pos'), 'Limiter "pos" belum terdaftar');
        $this->assertNotNull(RateLimiter::limiter('login'), 'Limiter "login" belum terdaftar');
    }

    public function test_limits_are_configurable_and_sane(): void
    {
        $this->assertGreaterThan(0, (int) config('rate_limit.pos_per_minute'));
        $this->assertGreaterThan(0, (int) config('rate_limit.login_per_minute'));
    }

    public function test_login_endpoint_returns_429_after_limit_exceeded(): void
    {
        config(['rate_limit.login_per_minute' => 3]);

        $payload = ['email' => 'tidak-ada@example.com', 'password' => 'salah'];

        for ($i = 0; $i < 3; $i++) {
            $this->post('/login', $payload);
        }

        $this->post('/login', $payload)->assertStatus(429);
    }
}
