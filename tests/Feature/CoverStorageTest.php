<?php

namespace Tests\Feature;

use App\Adapters\Outbound\Storage\FilesystemCoverStorage;
use App\Application\Contracts\IdGenerator;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class CoverStorageTest extends TestCase
{
    public function test_cover_storage_returns_an_opaque_key_and_supports_retrieval_and_removal(): void
    {
        Storage::fake('local');
        config(['filesystems.cover_disk' => 'local']);
        $ids = Mockery::mock(IdGenerator::class);
        $ids->shouldReceive('generate')->once()->andReturn('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa');

        $storage = new FilesystemCoverStorage($ids);
        $image = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/vQAAAABJRU5ErkJggg==', true);
        $key = $storage->store($image, 'image/png');

        $this->assertSame('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa.png', $key);
        $cover = $storage->retrieve($key);
        $this->assertNotNull($cover);
        $this->assertSame($image, $cover->contents);
        $storage->delete($key);
        $this->assertNull($storage->retrieve($key));
    }
}
