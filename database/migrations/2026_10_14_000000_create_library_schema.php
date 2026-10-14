<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE SCHEMA IF NOT EXISTS library');
        DB::statement('SET search_path TO library, public');

        Schema::create('categories', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name', 120)->unique('uq_categories_name');
        });

        Schema::create('books', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('title', 250);
            $table->string('author', 200);
            $table->char('isbn', 13);
            $table->uuid('category_id');
            $table->integer('total_copies');
            $table->integer('available_copies');
            $table->string('cover_key', 512)->nullable();
            $table->timestampTz('deleted_at')->nullable();
            $table->foreign('category_id', 'fk_books_category')
                ->references('id')->on('categories')->restrictOnDelete();
        });

        Schema::create('members', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('full_name', 200);
            $table->string('document_number', 30)->unique('uq_members_document_number');
        });

        Schema::create('users', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('username', 120)->unique('uq_users_username');
            $table->string('password_hash', 512);
            $table->string('role', 40);
        });

        Schema::create('loans', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('member_id');
            $table->uuid('loaned_by_user_id');
            $table->timestampTz('loaned_at');
            $table->foreign('member_id', 'fk_loans_member')
                ->references('id')->on('members')->restrictOnDelete();
            $table->foreign('loaned_by_user_id', 'fk_loans_user')
                ->references('id')->on('users')->restrictOnDelete();
        });

        Schema::create('loan_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('loan_id');
            $table->uuid('book_id');
            $table->string('book_title', 250);
            $table->char('isbn', 13);
            $table->string('category_name', 120);
            $table->timestampTz('due_at');
            $table->timestampTz('returned_at')->nullable();
            $table->foreign('loan_id', 'fk_loan_items_loan')
                ->references('id')->on('loans')->cascadeOnDelete();
            $table->foreign('book_id', 'fk_loan_items_book')
                ->references('id')->on('books')->restrictOnDelete();
            $table->unique(['loan_id', 'book_id'], 'uq_loan_items_loan_book');
        });

        DB::statement('ALTER TABLE books ADD CONSTRAINT ck_books_available_range CHECK (available_copies >= 0 AND available_copies <= total_copies)');
        DB::statement('CREATE UNIQUE INDEX uq_books_active_isbn ON books (isbn) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX ix_books_active_category_title ON books (category_id, title) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX ix_loans_loaned_at ON loans (loaned_at)');
        DB::statement('CREATE INDEX ix_loan_items_book_id ON loan_items (book_id)');
        DB::statement('CREATE INDEX ix_loan_items_overdue ON loan_items (due_at) WHERE returned_at IS NULL');

        DB::table('categories')->insert([
            ['id' => '11111111-1111-4111-8111-111111111111', 'name' => 'Novela'],
            ['id' => '22222222-2222-4222-8222-222222222222', 'name' => 'Ciencia'],
            ['id' => '33333333-3333-4333-8333-333333333333', 'name' => 'Tecnología'],
            ['id' => '44444444-4444-4444-8444-444444444444', 'name' => 'Historia'],
            ['id' => '55555555-5555-4555-8555-555555555555', 'name' => 'Infantil'],
        ]);
    }

    public function down(): void
    {
        DB::statement('SET search_path TO library, public');
        Schema::dropIfExists('loan_items');
        Schema::dropIfExists('loans');
        Schema::dropIfExists('users');
        Schema::dropIfExists('members');
        Schema::dropIfExists('books');
        Schema::dropIfExists('categories');
        DB::statement('DROP SCHEMA IF EXISTS library');
    }
};
