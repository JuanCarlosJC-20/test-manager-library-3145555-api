# Manager Library API

Laravel API for Manager Library. The implementation follows the reviewed specification in `test-manager-library-3145555-docs/spec-laravel`; its product requirements, schema, API contract, architecture, technical plan, and accepted ADRs are the source of truth.

## Requirements

- Laravel 13 and PHP 8.3 or later.
- PostgreSQL 16, using the `library` schema.
- Composer.

## Local development

1. Install PHP dependencies with `composer install`.
2. Copy `.env.example` to `.env` and configure the PostgreSQL connection, JWT secret, initial administrator credentials, frontend origin, and cover-storage settings.
3. Generate the Laravel application key with `php artisan key:generate`.
4. Run the application with `php artisan serve`.

The public health endpoint is `GET /health`. Versioned API routes use the `/api/v1` base path. Migrations own all application tables and the fixed category seed; the database service is expected to start empty.

## Verification

Run the automated suite with `php artisan test`. PostgreSQL integration and concurrency checks require a PostgreSQL 16 test database.
