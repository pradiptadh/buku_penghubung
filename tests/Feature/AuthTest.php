<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Login benar → diarahkan mengganti password bawaan.
     */
    public function test_guru_login_with_default_password_lands_on_password_page(): void
    {
        $user = User::factory()->create([
            'role' => 'guru',
            'password' => 'guru123',
            'using_default_password' => true,
        ]);

        $this->post(route('login.attempt'), [
            'email' => $user->email,
            'password' => 'guru123',
        ])->assertRedirect(route('password.edit'));

        $this->assertAuthenticatedAs($user);
    }

    /**
     * Password salah → pesan error, tetap tamu.
     */
    public function test_wrong_password_is_rejected(): void
    {
        $user = User::factory()->create(['role' => 'ortu']);

        $this->from(route('login'))
            ->post(route('login.attempt'), [
                'email' => $user->email,
                'password' => 'salah',
            ])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    /**
     * Ganti password menandai tidak lagi memakai bawaan.
     */
    public function test_password_change_clears_default_flag(): void
    {
        $user = User::factory()->create([
            'role' => 'guru',
            'password' => 'guru123',
            'using_default_password' => true,
        ]);

        $this->actingAs($user)
            ->post(route('password.update'), [
                'old' => 'guru123',
                'new' => 'rahasia-baru',
                'confirm' => 'rahasia-baru',
            ])
            ->assertRedirect(route('guru.kehadiran'));

        $this->assertFalse($user->fresh()->using_default_password);
        $this->assertTrue(
            Auth::validate([
                'email' => $user->email, 'password' => 'rahasia-baru',
            ])
        );
    }

    /**
     * Middleware peran: ortu tidak boleh masuk halaman guru.
     */
    public function test_ortu_cannot_access_guru_pages(): void
    {
        $ortu = User::factory()->create(['role' => 'ortu']);

        $this->actingAs($ortu)
            ->get(route('guru.kehadiran'))
            ->assertForbidden();
    }

    protected function tearDown(): void
    {
        RateLimiter::clear('testing');
        parent::tearDown();
    }
}
