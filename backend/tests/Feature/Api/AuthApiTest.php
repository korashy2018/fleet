<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_registers_and_returns_a_token(): void
    {
        $this->postJson('/api/v1/register', [
            'name' => 'Omar Hassan',
            'email' => 'omar@example.com',
            'password' => 'password',
        ])
            ->assertCreated()
            ->assertJsonPath('data.user.email', 'omar@example.com')
            ->assertJsonMissingPath('data.user.password');

        $this->assertDatabaseHas('users', ['email' => 'omar@example.com']);
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    #[Test]
    public function it_logs_in_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'omar@example.com',
            'password' => 'password',
        ]);

        $this->postJson('/api/v1/login', [
            'email' => 'omar@example.com',
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonStructure(['data' => ['token', 'user' => ['id', 'name', 'email']]]);
    }

    #[Test]
    public function it_rejects_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'omar@example.com',
            'password' => 'password',
        ]);

        $this->postJson('/api/v1/login', [
            'email' => 'omar@example.com',
            'password' => 'wrong-password',
        ])
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'invalid_credentials');
    }

    #[Test]
    public function it_returns_the_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->withToken($user->createToken('api')->plainTextToken)
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', $user->email);
    }

    #[Test]
    public function it_rejects_me_without_a_token(): void
    {
        $this->getJson('/api/v1/me')
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'unauthenticated');
    }

    #[Test]
    public function it_rejects_a_duplicate_email(): void
    {
        User::factory()->create(['email' => 'omar@example.com']);

        $this->postJson('/api/v1/register', [
            'name' => 'Omar Hassan',
            'email' => 'omar@example.com',
            'password' => 'password',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_error');
    }
}
