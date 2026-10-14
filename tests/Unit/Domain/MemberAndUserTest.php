<?php

namespace Tests\Unit\Domain;

use App\Domain\Members\DocumentNumber;
use App\Domain\Members\Member;
use App\Domain\Users\Role;
use App\Domain\Users\User;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MemberAndUserTest extends TestCase
{
    public function test_usernames_are_trimmed_and_lowercase(): void
    {
        $user = new User('user-id', ' Ana ', '$2y$12$hash', Role::Librarian);

        self::assertSame('ana', $user->username);
    }

    public function test_member_names_are_trimmed_and_document_numbers_are_bounded(): void
    {
        $member = new Member('member-id', ' Ana Pérez ', new DocumentNumber('CC-123'));

        self::assertSame('Ana Pérez', $member->fullName);
        self::assertSame('CC-123', $member->documentNumber->value);

        $this->expectException(InvalidArgumentException::class);
        new DocumentNumber(str_repeat('1', 31));
    }
}
