<?php

namespace App\Adapters\Outbound\Persistence;

use App\Application\Contracts\BookRepository;
use App\Application\Exceptions\IsbnTaken;
use App\Application\Exceptions\InvalidCategory;
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
            $term = '%'.trim($query).'%';
            $builder->where(function ($where) use ($term): void {
                $where->where('books.title', 'ILIKE', $term)
                    ->orWhere('books.author', 'ILIKE', $term);
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
            if ($exception->getCode() === '23503') {
                throw new InvalidCategory('La categoría solicitada no existe.');
            }

            throw $exception;
        }

        return $book;
    }

    public function update(Book $book): Book
    {
        return DB::transaction(function () use ($book): Book {
            $currentRow = DB::table('books')->where('id', $book->id)->whereNull('deleted_at')->lockForUpdate()->first();
            if ($currentRow === null) {
                throw new \App\Application\Exceptions\BookNotFound('No se encontró el libro.');
            }

            $current = self::entity($currentRow);
            $adjusted = $current->adjustTotalCopies($book->totalCopies);
            $updatedBook = new Book($book->id, $book->title, $book->author, $book->isbn, $book->categoryId,
                $adjusted->totalCopies, $adjusted->availableCopies, $current->coverKey);

            try {
                DB::table('books')->where('id', $book->id)->update([
                    'title' => $updatedBook->title,
                    'author' => trim($updatedBook->author),
                    'isbn' => $updatedBook->isbn->value,
                    'category_id' => $updatedBook->categoryId,
                    'total_copies' => $updatedBook->totalCopies,
                    'available_copies' => $updatedBook->availableCopies,
                ]);
            } catch (QueryException $exception) {
                if ($exception->getCode() === '23505') {
                    throw new IsbnTaken('El ISBN ya está asociado a un libro activo.');
                }
                if ($exception->getCode() === '23503') {
                    throw new InvalidCategory('La categoría solicitada no existe.');
                }

                throw $exception;
            }

            return $updatedBook;
        });
    }

    public function updateCoverKey(string $bookId, ?string $coverKey): bool
    {
        return DB::table('books')->where('id', $bookId)->whereNull('deleted_at')
            ->update(['cover_key' => $coverKey]) > 0;
    }

    public function softDelete(Book $book): void
    {
        DB::transaction(function () use ($book): void {
            $row = DB::table('books')->where('id', $book->id)->whereNull('deleted_at')->lockForUpdate()->first();
            if ($row === null) {
                throw new \App\Application\Exceptions\BookNotFound('No se encontró el libro.');
            }

            $hasOpenLoans = DB::table('loan_items')->where('book_id', $book->id)->whereNull('returned_at')->exists();
            if ($hasOpenLoans) {
                throw new \App\Application\Exceptions\BookHasOpenLoans('El libro tiene préstamos sin devolver.');
            }

            DB::table('books')->where('id', $book->id)->update([
                'deleted_at' => $book->deletedAt?->format('Y-m-d H:i:s.uP'),
            ]);
        });
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
