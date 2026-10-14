<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE library.books ADD CONSTRAINT ck_books_total_positive CHECK (total_copies >= 1)');
        DB::statement("ALTER TABLE library.users ADD CONSTRAINT ck_users_role CHECK (role IN ('admin', 'librarian'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE library.users DROP CONSTRAINT ck_users_role');
        DB::statement('ALTER TABLE library.books DROP CONSTRAINT ck_books_total_positive');
    }
};
