<?php

namespace Tests\Feature;

use App\Application\Contracts\MostBorrowedReport;
use App\Application\Contracts\TokenVerifier;
use App\Application\Data\TokenClaims;
use App\Domain\Users\Role;
use DateTimeImmutable;
use Mockery;
use Tests\TestCase;

class ReportApiTest extends TestCase
{
    private function authenticate(Role $role): void
    {
        $verifier = Mockery::mock(TokenVerifier::class);
        $verifier->shouldReceive('verify')->andReturn(new TokenClaims('admin-id', $role, new DateTimeImmutable('+1 hour')));
        $this->app->instance(TokenVerifier::class, $verifier);
    }

    public function test_admin_can_view_snapshot_based_most_borrowed_report(): void
    {
        $this->authenticate(Role::Admin);
        $report = Mockery::mock(MostBorrowedReport::class);
        $report->shouldReceive('forPeriod')->once()->with('2026-10-01', '2026-10-31')->andReturn([
            ['book_id' => 'book-id', 'book_title' => 'Título guardado', 'category_name' => 'Novela', 'times_borrowed' => 3],
        ]);
        $this->app->instance(MostBorrowedReport::class, $report);

        $this->withToken('admin-token')->getJson('/api/v1/reports/most-borrowed?from=2026-10-01&to=2026-10-31')
            ->assertOk()->assertJsonPath('rows.0.times_borrowed', 3)
            ->assertJsonMissingPath('rows.0.member_name');
    }

    public function test_report_is_admin_only(): void
    {
        $this->authenticate(Role::Librarian);

        $this->withToken('librarian-token')->getJson('/api/v1/reports/most-borrowed?from=2026-10-01&to=2026-10-31')
            ->assertForbidden()->assertJsonPath('code', 'FORBIDDEN_ROLE');
    }

    public function test_report_rejects_a_reversed_date_range(): void
    {
        $this->authenticate(Role::Admin);

        $this->withToken('admin-token')->getJson('/api/v1/reports/most-borrowed?from=2026-10-31&to=2026-10-01')
            ->assertUnprocessable()->assertJsonPath('code', 'INVALID_DATE_RANGE');
    }
}
