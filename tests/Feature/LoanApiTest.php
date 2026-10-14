<?php

namespace Tests\Feature;

use App\Application\Contracts\Clock;
use App\Application\Contracts\IdGenerator;
use App\Application\Contracts\LoanRepository;
use App\Application\Contracts\TokenVerifier;
use App\Application\Data\TokenClaims;
use App\Domain\Books\Isbn;
use App\Domain\Loans\Loan;
use App\Domain\Loans\LoanItem;
use App\Domain\Users\Role;
use DateTimeImmutable;
use Mockery;
use Tests\TestCase;

class LoanApiTest extends TestCase
{
    private function authenticate(Role $role = Role::Librarian): void
    {
        $verifier = Mockery::mock(TokenVerifier::class);
        $verifier->shouldReceive('verify')->andReturn(new TokenClaims('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', $role, new DateTimeImmutable('+1 hour')));
        $this->app->instance(TokenVerifier::class, $verifier);
    }

    public function test_loan_creation_uses_authenticated_operator_and_returns_snapshot(): void
    {
        $this->authenticate();
        $now = new DateTimeImmutable('2026-10-14T12:00:00Z');
        $loan = new Loan('loan-id', 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', $now, [
            new LoanItem('item-id', 'cccccccc-cccc-4ccc-8ccc-cccccccccccc', 'Libro', new Isbn('9780307474728'), 'Novela', $now->modify('+14 days')),
        ]);
        $loans = Mockery::mock(LoanRepository::class);
        $loans->shouldReceive('createLoan')->once()->andReturn($loan);
        $this->app->instance(LoanRepository::class, $loans);
        $clock = Mockery::mock(Clock::class);
        $clock->shouldReceive('now')->andReturn($now);
        $this->app->instance(Clock::class, $clock);
        $ids = Mockery::mock(IdGenerator::class);
        $ids->shouldReceive('generate')->twice()->andReturn('loan-id', 'item-id');
        $this->app->instance(IdGenerator::class, $ids);

        $this->withToken('librarian-token')->postJson('/api/v1/loans', [
            'member_id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'items' => [['book_id' => 'cccccccc-cccc-4ccc-8ccc-cccccccccccc']],
        ])->assertCreated()->assertJsonPath('status', 'open')
            ->assertJsonPath('items.0.book_title', 'Libro')
            ->assertJsonPath('items.0.due_at', '2026-10-28T12:00:00Z');
    }

    public function test_repeating_book_in_a_loan_returns_documented_business_error(): void
    {
        $this->authenticate();
        $clock = Mockery::mock(Clock::class);
        $clock->shouldReceive('now')->andReturn(new DateTimeImmutable);
        $this->app->instance(Clock::class, $clock);

        $this->withToken('librarian-token')->postJson('/api/v1/loans', [
            'member_id' => '11111111-1111-4111-8111-111111111111',
            'items' => [
                ['book_id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa'],
                ['book_id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa'],
            ],
        ])->assertUnprocessable()->assertJsonPath('code', 'DUPLICATE_BOOK_IN_LOAN');
    }
}
