<?php

namespace App\Providers;

use App\Adapters\Outbound\Persistence\EloquentBookRepository;
use App\Adapters\Outbound\Persistence\EloquentMemberRepository;
use App\Adapters\Outbound\Persistence\EloquentLoanRepository;
use App\Adapters\Outbound\Persistence\EloquentMostBorrowedReport;
use App\Adapters\Outbound\Persistence\EloquentCategoryRepository;
use App\Adapters\Outbound\Persistence\EloquentUserRepository;
use App\Adapters\Outbound\Security\BcryptPasswordHasher;
use App\Adapters\Outbound\Security\JwtTokenService;
use App\Adapters\Outbound\Storage\FilesystemCoverStorage;
use App\Adapters\Outbound\Support\SystemClock;
use App\Adapters\Outbound\Support\UuidGenerator;
use App\Application\Contracts\BookRepository;
use App\Application\Contracts\MemberRepository;
use App\Application\Contracts\LoanRepository;
use App\Application\Contracts\MostBorrowedReport;
use App\Application\Contracts\CategoryRepository;
use App\Application\Contracts\Clock;
use App\Application\Contracts\CoverStorage;
use App\Application\Contracts\IdGenerator;
use App\Application\Contracts\PasswordHasher;
use App\Application\Contracts\TokenIssuer;
use App\Application\Contracts\TokenVerifier;
use App\Application\Contracts\UserRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(UserRepository::class, EloquentUserRepository::class);
        $this->app->bind(CategoryRepository::class, EloquentCategoryRepository::class);
        $this->app->bind(BookRepository::class, EloquentBookRepository::class);
        $this->app->bind(MemberRepository::class, EloquentMemberRepository::class);
        $this->app->bind(LoanRepository::class, EloquentLoanRepository::class);
        $this->app->bind(MostBorrowedReport::class, EloquentMostBorrowedReport::class);
        $this->app->bind(CoverStorage::class, FilesystemCoverStorage::class);
        $this->app->singleton(Clock::class, SystemClock::class);
        $this->app->singleton(IdGenerator::class, UuidGenerator::class);
        $this->app->singleton(PasswordHasher::class, BcryptPasswordHasher::class);
        $this->app->singleton(JwtTokenService::class, fn ($app) => new JwtTokenService(
            $app->make(Clock::class),
            config('jwt.secret'),
        ));
        $this->app->alias(JwtTokenService::class, TokenIssuer::class);
        $this->app->alias(JwtTokenService::class, TokenVerifier::class);
    }
}
