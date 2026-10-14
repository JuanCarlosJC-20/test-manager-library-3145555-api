<?php

namespace Tests\Feature;

use App\Application\Contracts\IdGenerator;
use App\Application\Contracts\PasswordHasher;
use App\Application\Contracts\TokenIssuer;
use App\Application\Contracts\TokenVerifier;
use App\Application\Contracts\UserRepository;
use App\Application\Data\IssuedToken;
use App\Application\Data\TokenClaims;
use App\Application\Exceptions\InvalidToken;
use App\Domain\Users\Role;
use App\Domain\Users\User;
use DateTimeImmutable;
use Mockery;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    public function test_login_returns_bearer_token_and_role(): void
    {
        $user = new User('user-id', 'admin', 'hashed-password', Role::Admin);
        $users = Mockery::mock(UserRepository::class);
        $users->shouldReceive('findByUsername')->once()->with('admin')->andReturn($user);
        $passwords = Mockery::mock(PasswordHasher::class);
        $passwords->shouldReceive('verify')->once()->with('secret', 'hashed-password')->andReturnTrue();
        $tokens = Mockery::mock(TokenIssuer::class);
        $tokens->shouldReceive('issue')->once()->with($user)->andReturn(new IssuedToken('signed-jwt', 28800, Role::Admin));

        $this->app->instance(UserRepository::class, $users);
        $this->app->instance(PasswordHasher::class, $passwords);
        $this->app->instance(TokenIssuer::class, $tokens);

        $this->postJson('/api/v1/auth/login', ['username' => ' Admin ', 'password' => 'secret'])
            ->assertOk()
            ->assertExactJson([
                'access_token' => 'signed-jwt',
                'token_type' => 'Bearer',
                'expires_in' => 28800,
                'role' => 'admin',
            ]);
    }

    public function test_invalid_login_uses_the_common_error_envelope(): void
    {
        $users = Mockery::mock(UserRepository::class);
        $users->shouldReceive('findByUsername')->once()->andReturnNull();
        $passwords = Mockery::mock(PasswordHasher::class);
        $passwords->shouldReceive('hash')->once()->with('wrong');
        $this->app->instance(UserRepository::class, $users);
        $this->app->instance(PasswordHasher::class, $passwords);

        $this->postJson('/api/v1/auth/login', ['username' => 'missing', 'password' => 'wrong'])
            ->assertUnauthorized()
            ->assertExactJson([
                'code' => 'INVALID_CREDENTIALS',
                'message' => 'Las credenciales no son válidas.',
                'details' => [],
            ]);
    }

    public function test_protected_route_requires_a_valid_admin_token(): void
    {
        $this->postJson('/api/v1/users', ['username' => 'librarian', 'password' => 'secret', 'role' => 'librarian'])
            ->assertUnauthorized()
            ->assertJsonPath('code', 'UNAUTHENTICATED');

        $verifier = Mockery::mock(TokenVerifier::class);
        $verifier->shouldReceive('verify')->once()->with('bad-token')->andThrow(new InvalidToken('invalid'));
        $this->app->instance(TokenVerifier::class, $verifier);

        $this->withToken('bad-token')->postJson('/api/v1/users', [])
            ->assertUnauthorized()
            ->assertJsonPath('code', 'UNAUTHENTICATED');
    }

    public function test_librarian_cannot_create_users(): void
    {
        $verifier = Mockery::mock(TokenVerifier::class);
        $verifier->shouldReceive('verify')->once()->with('librarian-token')->andReturn(
            new TokenClaims('librarian-id', Role::Librarian, new DateTimeImmutable('+1 hour')),
        );
        $this->app->instance(TokenVerifier::class, $verifier);

        $this->withToken('librarian-token')->postJson('/api/v1/users', [])
            ->assertForbidden()
            ->assertJsonPath('code', 'FORBIDDEN_ROLE');
    }

    public function test_admin_can_create_a_librarian_without_exposing_the_password_hash(): void
    {
        $verifier = Mockery::mock(TokenVerifier::class);
        $verifier->shouldReceive('verify')->once()->with('admin-token')->andReturn(
            new TokenClaims('admin-id', Role::Admin, new DateTimeImmutable('+1 hour')),
        );
        $users = Mockery::mock(UserRepository::class);
        $users->shouldReceive('findByUsername')->once()->with('ana')->andReturnNull();
        $users->shouldReceive('create')->once()->with(Mockery::on(
            fn (User $user): bool => $user->username === 'ana'
                && $user->passwordHash === 'hashed-secret'
                && $user->role === Role::Librarian,
        ))->andReturn(new User('new-user-id', 'ana', 'hashed-secret', Role::Librarian));
        $passwords = Mockery::mock(PasswordHasher::class);
        $passwords->shouldReceive('hash')->once()->with('secret')->andReturn('hashed-secret');
        $ids = Mockery::mock(IdGenerator::class);
        $ids->shouldReceive('generate')->once()->andReturn('new-user-id');
        $this->app->instance(TokenVerifier::class, $verifier);
        $this->app->instance(UserRepository::class, $users);
        $this->app->instance(PasswordHasher::class, $passwords);
        $this->app->instance(IdGenerator::class, $ids);

        $this->withToken('admin-token')->postJson('/api/v1/users', [
            'username' => ' Ana ',
            'password' => 'secret',
            'role' => 'librarian',
        ])->assertCreated()->assertExactJson([
            'id' => 'new-user-id',
            'username' => 'ana',
            'role' => 'librarian',
        ])->assertJsonMissingPath('password')->assertJsonMissingPath('password_hash');
    }

    public function test_user_creation_rejects_admin_role_with_validation_envelope(): void
    {
        $verifier = Mockery::mock(TokenVerifier::class);
        $verifier->shouldReceive('verify')->once()->with('admin-token')->andReturn(
            new TokenClaims('admin-id', Role::Admin, new DateTimeImmutable('+1 hour')),
        );
        $this->app->instance(TokenVerifier::class, $verifier);

        $this->withToken('admin-token')->postJson('/api/v1/users', [
            'username' => 'new-user',
            'password' => 'secret',
            'role' => 'admin',
        ])->assertUnprocessable()
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonPath('details.0.field', 'role');
    }
}
