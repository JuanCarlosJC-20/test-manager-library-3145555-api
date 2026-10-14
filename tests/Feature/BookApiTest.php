<?php

namespace Tests\Feature;

use App\Application\Contracts\BookRepository;
use App\Application\Contracts\CategoryRepository;
use App\Application\Contracts\CoverStorage;
use App\Application\Contracts\IdGenerator;
use App\Application\Contracts\TokenVerifier;
use App\Application\Data\StoredCover;
use App\Application\Data\TokenClaims;
use App\Application\Exceptions\IsbnTaken;
use App\Domain\Books\Book;
use App\Domain\Books\Isbn;
use App\Domain\Users\Role;
use DateTimeImmutable;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

class BookApiTest extends TestCase
{
    private function authenticateAsAdmin(): void
    {
        $verifier = Mockery::mock(TokenVerifier::class);
        $verifier->shouldReceive('verify')->andReturn(
            new TokenClaims('admin-id', Role::Admin, new DateTimeImmutable('+1 hour')),
        );
        $this->app->instance(TokenVerifier::class, $verifier);
    }

    public function test_search_returns_paginated_book_representations(): void
    {
        $this->authenticateAsAdmin();
        $books = Mockery::mock(BookRepository::class);
        $books->shouldReceive('search')->once()->with('sol', null, 2, 10)->andReturn([
            'items' => [[
                'id' => 'book-id', 'title' => 'Cien años de soledad', 'author' => 'Gabriel García Márquez',
                'isbn' => '9780307474728', 'category' => ['id' => 'category-id', 'name' => 'Novela'],
                'total_copies' => 4, 'available_copies' => 3, 'cover_key' => null,
            ]],
            'page' => 2, 'size' => 10, 'total' => 11,
        ]);
        $this->app->instance(BookRepository::class, $books);

        $this->withToken('admin-token')->getJson('/api/v1/books?q=sol&page=2&size=10')
            ->assertOk()->assertExactJson([
                'items' => [[
                    'id' => 'book-id', 'title' => 'Cien años de soledad', 'author' => 'Gabriel García Márquez',
                    'isbn' => '9780307474728', 'category' => ['id' => 'category-id', 'name' => 'Novela'],
                    'total_copies' => 4, 'available_copies' => 3, 'cover_key' => null,
                ]],
                'page' => 2, 'size' => 10, 'total' => 11,
            ]);
    }

    public function test_admin_creates_a_book_with_all_copies_available(): void
    {
        $this->authenticateAsAdmin();
        $book = new Book('book-id', 'Libro nuevo', 'Autor', new Isbn('9780307474728'), '11111111-1111-4111-8111-111111111111', 4, 4);
        $books = Mockery::mock(BookRepository::class);
        $books->shouldReceive('create')->once()->with(Mockery::on(
            fn (Book $value): bool => $value->totalCopies === 4 && $value->availableCopies === 4,
        ))->andReturn($book);
        $this->app->instance(BookRepository::class, $books);
        $this->app->instance(IdGenerator::class, Mockery::mock(IdGenerator::class, [])->shouldReceive('generate')->andReturn('book-id')->getMock());
        $categories = Mockery::mock(CategoryRepository::class);
        $categories->shouldReceive('findName')->once()->with('11111111-1111-4111-8111-111111111111')->andReturn('Novela');
        $this->app->instance(CategoryRepository::class, $categories);

        $this->withToken('admin-token')->postJson('/api/v1/books', [
            'title' => 'Libro nuevo', 'author' => 'Autor', 'isbn' => '9780307474728',
            'category_id' => '11111111-1111-4111-8111-111111111111', 'total_copies' => 4,
        ])->assertCreated()->assertJsonPath('available_copies', 4)
            ->assertJsonPath('category.name', 'Novela');
    }

    public function test_invalid_isbn_returns_validation_error(): void
    {
        $this->authenticateAsAdmin();

        $this->withToken('admin-token')->postJson('/api/v1/books', [
            'title' => 'Libro', 'author' => 'Autor', 'isbn' => 'invalid',
            'category_id' => '11111111-1111-4111-8111-111111111111', 'total_copies' => 1,
        ])->assertUnprocessable()->assertJsonPath('code', 'VALIDATION_ERROR');
    }

    public function test_duplicate_isbn_maps_to_conflict(): void
    {
        $this->authenticateAsAdmin();
        $books = Mockery::mock(BookRepository::class);
        $books->shouldReceive('create')->once()->andThrow(new IsbnTaken('duplicate'));
        $this->app->instance(BookRepository::class, $books);
        $ids = Mockery::mock(IdGenerator::class);
        $ids->shouldReceive('generate')->once()->andReturn('book-id');
        $this->app->instance(IdGenerator::class, $ids);

        $this->withToken('admin-token')->postJson('/api/v1/books', [
            'title' => 'Libro', 'author' => 'Autor', 'isbn' => '9780307474728',
            'category_id' => '11111111-1111-4111-8111-111111111111', 'total_copies' => 1,
        ])->assertStatus(409)->assertJsonPath('code', 'ISBN_TAKEN');
    }

    public function test_cover_upload_stores_validated_image_and_returns_no_content(): void
    {
        $this->authenticateAsAdmin();
        $book = new Book('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'Libro', 'Autor', new Isbn('9780307474728'), 'category-id', 1, 1);
        $books = Mockery::mock(BookRepository::class);
        $books->shouldReceive('findActive')->once()->with($book->id)->andReturn($book);
        $books->shouldReceive('updateCoverKey')->once()->with($book->id, 'opaque-cover.jpg')->andReturnTrue();
        $this->app->instance(BookRepository::class, $books);
        $coverStorage = Mockery::mock(CoverStorage::class);
        $coverStorage->shouldReceive('store')->once()->with(Mockery::type('string'), 'image/jpeg')->andReturn('opaque-cover.jpg');
        $this->app->instance(CoverStorage::class, $coverStorage);
        $jpeg = base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAX/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAAF//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABBQJ//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAwEBPwF//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAgEBPwF//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQAGPwJ//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPyF//9oADAMBAAIAAwAAABAf/8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAwEBPxB//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAgEBPxB//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxB//9k=', true);

        $this->withToken('admin-token')->put('/api/v1/books/'.$book->id.'/cover', [
            'file' => UploadedFile::fake()->createWithContent('cover.jpg', $jpeg),
        ])->assertNoContent();
    }

    public function test_cover_upload_rejects_content_that_is_not_jpeg_or_png(): void
    {
        $this->authenticateAsAdmin();

        $this->withToken('admin-token')->put('/api/v1/books/11111111-1111-4111-8111-111111111111/cover', [
            'file' => UploadedFile::fake()->createWithContent('fake.jpg', 'not an image'),
        ])->assertUnprocessable()->assertJsonPath('code', 'VALIDATION_ERROR');
    }

    public function test_cover_retrieval_returns_image_bytes_and_content_type(): void
    {
        $this->authenticateAsAdmin();
        $book = new Book('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'Libro', 'Autor', new Isbn('9780307474728'), 'category-id', 1, 1, 'opaque-cover.png');
        $books = Mockery::mock(BookRepository::class);
        $books->shouldReceive('findActive')->once()->with($book->id)->andReturn($book);
        $storage = Mockery::mock(CoverStorage::class);
        $storage->shouldReceive('retrieve')->once()->with('opaque-cover.png')->andReturn(new StoredCover('image-bytes', 'image/png'));
        $this->app->instance(BookRepository::class, $books);
        $this->app->instance(CoverStorage::class, $storage);

        $this->withToken('admin-token')->get('/api/v1/books/'.$book->id.'/cover')
            ->assertOk()->assertHeader('Content-Type', 'image/png')->assertContent('image-bytes');
    }
}
