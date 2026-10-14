<?php

namespace Tests\Feature;

use App\Application\Contracts\IdGenerator;
use App\Application\Contracts\MemberRepository;
use App\Application\Contracts\TokenVerifier;
use App\Application\Data\TokenClaims;
use App\Application\Exceptions\DocumentTaken;
use App\Domain\Members\DocumentNumber;
use App\Domain\Members\Member;
use App\Domain\Users\Role;
use DateTimeImmutable;
use Mockery;
use Tests\TestCase;

class MemberApiTest extends TestCase
{
    private function authenticate(Role $role = Role::Librarian): void
    {
        $verifier = Mockery::mock(TokenVerifier::class);
        $verifier->shouldReceive('verify')->andReturn(new TokenClaims('operator-id', $role, new DateTimeImmutable('+1 hour')));
        $this->app->instance(TokenVerifier::class, $verifier);
    }

    public function test_librarian_can_create_a_member(): void
    {
        $this->authenticate();
        $members = Mockery::mock(MemberRepository::class);
        $members->shouldReceive('create')->once()->with(Mockery::on(
            fn (Member $member): bool => $member->fullName === 'Ana Pérez'
                && $member->documentNumber->value === 'CC-123',
        ))->andReturn(new Member('member-id', 'Ana Pérez', new DocumentNumber('CC-123')));
        $this->app->instance(MemberRepository::class, $members);
        $ids = Mockery::mock(IdGenerator::class);
        $ids->shouldReceive('generate')->once()->andReturn('member-id');
        $this->app->instance(IdGenerator::class, $ids);

        $this->withToken('librarian-token')->postJson('/api/v1/members', [
            'full_name' => ' Ana Pérez ', 'document_number' => ' CC-123 ',
        ])->assertCreated()->assertExactJson([
            'id' => 'member-id', 'full_name' => 'Ana Pérez', 'document_number' => 'CC-123',
        ]);
    }

    public function test_duplicate_member_document_returns_conflict(): void
    {
        $this->authenticate(Role::Admin);
        $members = Mockery::mock(MemberRepository::class);
        $members->shouldReceive('create')->once()->andThrow(new DocumentTaken('duplicate'));
        $this->app->instance(MemberRepository::class, $members);
        $ids = Mockery::mock(IdGenerator::class);
        $ids->shouldReceive('generate')->once()->andReturn('member-id');
        $this->app->instance(IdGenerator::class, $ids);

        $this->withToken('admin-token')->postJson('/api/v1/members', [
            'full_name' => 'Ana', 'document_number' => 'CC-123',
        ])->assertStatus(409)->assertJsonPath('code', 'DOCUMENT_TAKEN');
    }

    public function test_member_detail_includes_active_loan_count(): void
    {
        $this->authenticate();
        $members = Mockery::mock(MemberRepository::class);
        $members->shouldReceive('find')->once()->with('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa')
            ->andReturn(new Member('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'Ana Pérez', new DocumentNumber('CC-123')));
        $members->shouldReceive('activeLoanCount')->once()->with('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa')->andReturn(2);
        $this->app->instance(MemberRepository::class, $members);

        $this->withToken('librarian-token')->getJson('/api/v1/members/aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa')
            ->assertOk()->assertJsonPath('active_loans', 2);
    }
}
