<?php

namespace Tests\Feature;

use App\Adapters\Outbound\Persistence\EloquentLoanRepository;
use App\Application\Data\LoanCreation;
use App\Application\Exceptions\MemberLimitExceeded;
use App\Domain\Books\BookNotAvailable;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PostgresLoanRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    private const string USER_ID = 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';
    private const string MEMBER_ID = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
    private const string BOOK_ID = 'cccccccc-cccc-4ccc-8ccc-cccccccccccc';
    private const string CATEGORY_ID = '11111111-1111-4111-8111-111111111111';

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'pgsql' || ! Schema::hasTable('books')) {
            $this->markTestSkipped('This integration test requires the migrated PostgreSQL library schema.');
        }

        DB::table('users')->insert([
            'id' => self::USER_ID,
            'username' => 'postgres-loan-test',
            'password_hash' => password_hash('not-used', PASSWORD_BCRYPT),
            'role' => 'admin',
        ]);
        DB::table('members')->insert([
            'id' => self::MEMBER_ID,
            'full_name' => 'Integration Member',
            'document_number' => 'PG-LOAN-TEST',
        ]);
        DB::table('books')->insert([
            'id' => self::BOOK_ID,
            'title' => 'Snapshot title',
            'author' => 'Integration Author',
            'isbn' => '9780307474728',
            'category_id' => self::CATEGORY_ID,
            'total_copies' => 1,
            'available_copies' => 1,
            'cover_key' => null,
            'deleted_at' => null,
        ]);
    }

    public function test_loan_creation_atomically_decrements_inventory_and_saves_snapshots(): void
    {
        $now = new DateTimeImmutable('2026-10-14T12:00:00Z');
        $loan = app(EloquentLoanRepository::class)->createLoan(new LoanCreation(
            'dddddddd-dddd-4ddd-8ddd-dddddddddddd', self::MEMBER_ID, self::USER_ID,
            [self::BOOK_ID], ['eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee'], $now,
        ));

        $this->assertSame('Snapshot title', $loan->items[0]->bookTitle);
        $this->assertSame('Novela', $loan->items[0]->categoryName);
        $this->assertSame(0, (int) DB::table('books')->where('id', self::BOOK_ID)->value('available_copies'));
        $this->assertSame(1, DB::table('loan_items')->where('loan_id', $loan->id)->count());
    }

    public function test_unavailable_book_creates_no_partial_loan(): void
    {
        DB::table('books')->where('id', self::BOOK_ID)->update(['available_copies' => 0]);

        try {
            app(EloquentLoanRepository::class)->createLoan(new LoanCreation(
                'dddddddd-dddd-4ddd-8ddd-dddddddddddd', self::MEMBER_ID, self::USER_ID,
                [self::BOOK_ID], ['eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee'], new DateTimeImmutable('2026-10-14T12:00:00Z'),
            ));
            $this->fail('Expected an unavailable-book conflict.');
        } catch (BookNotAvailable) {
            $this->assertSame(0, DB::table('loans')->where('id', 'dddddddd-dddd-4ddd-8ddd-dddddddddddd')->count());
            $this->assertSame(0, (int) DB::table('books')->where('id', self::BOOK_ID)->value('available_copies'));
        }
    }

    public function test_member_limit_is_rechecked_inside_the_loan_transaction(): void
    {
        foreach (range(1, 3) as $index) {
            $loanId = sprintf('dddddddd-dddd-4ddd-8ddd-%012d', $index);
            DB::table('loans')->insert([
                'id' => $loanId,
                'member_id' => self::MEMBER_ID,
                'loaned_by_user_id' => self::USER_ID,
                'loaned_at' => '2026-10-01T12:00:00Z',
            ]);
            DB::table('loan_items')->insert([
                'id' => sprintf('eeeeeeee-eeee-4eee-8eee-%012d', $index),
                'loan_id' => $loanId,
                'book_id' => self::BOOK_ID,
                'book_title' => 'Existing title',
                'isbn' => sprintf('9780307474%03d', $index),
                'category_name' => 'Novela',
                'due_at' => '2026-11-01T12:00:00Z',
                'returned_at' => null,
            ]);
        }

        $this->expectException(MemberLimitExceeded::class);
        app(EloquentLoanRepository::class)->createLoan(new LoanCreation(
            'ffffffff-ffff-4fff-8fff-ffffffffffff', self::MEMBER_ID, self::USER_ID,
            [self::BOOK_ID], ['11111111-2222-4333-8444-555555555555'], new DateTimeImmutable('2026-10-14T12:00:00Z'),
        ));
    }
}
