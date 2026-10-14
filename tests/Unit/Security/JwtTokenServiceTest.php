<?php

namespace Tests\Unit\Security;

use App\Adapters\Outbound\Security\JwtTokenService;
use App\Adapters\Outbound\Support\SystemClock;
use App\Domain\Users\Role;
use App\Domain\Users\User;
use PHPUnit\Framework\TestCase;

class JwtTokenServiceTest extends TestCase
{
    public function test_issued_token_contains_only_subject_role_and_eight_hour_lifetime(): void
    {
        $tokens = new JwtTokenService(new SystemClock, str_repeat('s', 40));
        $issued = $tokens->issue(new User(
            'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
            'operator',
            'bcrypt-hash-value',
            Role::Librarian,
        ));

        $claims = $tokens->verify($issued->accessToken);

        $this->assertSame('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', $claims->subject);
        $this->assertSame(Role::Librarian, $claims->role);
        $this->assertSame(28800, $issued->expiresIn);
        $this->assertGreaterThan(time(), $claims->expiresAt->getTimestamp());
    }

    public function test_invalid_signature_is_rejected(): void
    {
        $issuer = new JwtTokenService(new SystemClock, str_repeat('a', 40));
        $token = $issuer->issue(new User(
            'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'operator', 'bcrypt-hash-value', Role::Admin,
        ));
        $verifier = new JwtTokenService(new SystemClock, str_repeat('b', 40));

        $this->expectException(\App\Application\Exceptions\InvalidToken::class);
        $verifier->verify($token->accessToken);
    }
}
