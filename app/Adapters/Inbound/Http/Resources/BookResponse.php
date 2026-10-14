<?php

namespace App\Adapters\Inbound\Http\Resources;

use App\Domain\Books\Book;

final class BookResponse
{
    public static function from(Book $book, string $categoryName): array
    {
        return [
            'id' => $book->id,
            'title' => $book->title,
            'author' => $book->author,
            'isbn' => $book->isbn->value,
            'category' => ['id' => $book->categoryId, 'name' => $categoryName],
            'total_copies' => $book->totalCopies,
            'available_copies' => $book->availableCopies,
            'cover_key' => $book->coverKey,
        ];
    }
}
