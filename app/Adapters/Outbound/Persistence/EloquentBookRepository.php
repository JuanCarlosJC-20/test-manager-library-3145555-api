<?php

namespace App\Adapters\Outbound\Persistence;

use App\Application\Contracts\BookRepository;
use App\Application\Exceptions\IsbnTaken;
use App\Domain\Books\Book;
use App\Domain\Books\Isbn;
use DateTimeImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final class EloquentBookRepository implements BookRepository
{
    public function search(?string $query, ?string $categoryId, int $page, int $size): array
    {
        $builder = DB::table('books')->join('categories', 'categories.id', '=', 'books.category_id')
            ->whereNull('books.deleted_at');

        if ($query !== null && trim($query) !== '') {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim($query)).'%';
            $builder->where(function ($where) use ($term): void {
                $where->whereRaw("books.title ILIKE ? ESCAPE '\\\\'", [$term])
                    ->orWhereRaw("books.author ILIKE ? ESCAPE '\\\\'", [$term]);
            });
        }

        if ($categoryId !== null && $categoryId !== '') {
            $builder->where('books.category_id', $categoryId);
        }

        $total = (clone $builder)->count('books.id');
        $rows = $builder->orderBy('books.title')->forPage($page, $size)
            ->get(['books.id', 'books.title', 'books.author', 'books.isbn', 'books.category_id',
                'books.total_copies', 'books.available_copies', 'books.cover_key', 'categories.name as category_name']);

        return [
            'items' => $rows->map(static fn (object $row): array => self::representation($row))->all(),
            'page' => $page,
            'size' => $size,
            'total' => $total,
        ];
    }

    public function findActive(string $id): ?Book
    {
        $row = DB::table('books')->where('id', $id)->whereNull('deleted_at')->first();

        return $row === null ? null : self::entity($row);
    }

    public function create(Book $book): Book
    {
        try {
            DB::table('books')->insert(self::attributes($book));
        } catch (QueryException $exception) {
            if ($exception->getCode() === '23505') {
                throw new IsbnTaken('El ISBN ya está asociado a un libro activo.');
            }

            throw $exception;
        }

        return $book;
    }

    public function update(Book $book): Book
    {
        try {
            $updated = DB::table('books')->where('id', $book->id)->whereNull('deleted_at')->update(self::attributes($book));
        } catch (QueryException $exception) {
            if ($exception->getCode() === '23505') {
                throw new IsbnTaken('El ISBN ya está asociado a un libro activo.');
            }

            throw $exception;
        }

        if ($updated === 0) {
            throw new \RuntimeException('No se pudo actualizar el libro.');
        }

        return $book;
    }

    public function hasOpenLoans(string $bookId): bool
    {
        return DB::table('loan_items')->where('book_id', $bookId)->whereNull('returned_at')->exists();
    }

    public function softDelete(Book $book): void
    {
        DB::table('books')->where('id', $book->id)->whereNull('deleted_at')
            ->update(['deleted_at' => $book->deletedAt?->format('Y-m-d H:i:s.uP')]);
    }

    private static function attributes(Book $book): array
    {
        return [
            'id' => $book->id,
            'title' => $book->title,
            'author' => trim($book->author),
            'isbn' => $book->isbn->value,
            'category_id' => $book->categoryId,
            'total_copies' => $book->totalCopies,
            'available_copies' => $book->availableCopies,
            'cover_key' => $book->coverKey,
            'deleted_at' => $book->deletedAt?->format('Y-m-d H:i:s.uP'),
        ];
    }

    private static function entity(object $row): Book
    {
        return new Book(
            (string) $row->id,
            $row->title,
            $row->author,
            new Isbn(trim($row->isbn)),
            (string) $row->category_id,
            (int) $row->total_copies,
            (int) $row->available_copies,
            $row->cover_key,
            isset($row->deleted_at) && $row->deleted_at !== null ? new DateTimeImmutable($row->deleted_at) : null,
        );
    }

    private static function representation(object $row): array
    {
        return [
            'id' => (string) $row->id,
            'title' => $row->title,
            'author' => $row->author,
            'isbn' => trim($row->isbn),
            'category' => ['id' => (string) $row->category_id, 'name' => $row->category_name],
            'total_copies' => (int) $row->total_copies,
            'available_copies' => (int) $row->available_copies,
            'cover_key' => $row->cover_key,
        ];
    }
}
