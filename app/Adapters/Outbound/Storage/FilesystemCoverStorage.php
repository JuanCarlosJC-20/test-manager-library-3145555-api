<?php

namespace App\Adapters\Outbound\Storage;

use App\Application\Contracts\CoverStorage;
use App\Application\Contracts\IdGenerator;
use App\Application\Data\StoredCover;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final readonly class FilesystemCoverStorage implements CoverStorage
{
    public function __construct(private IdGenerator $ids) {}

    public function store(string $contents, string $mimeType): string
    {
        $extension = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            default => throw new RuntimeException('Unsupported cover media type.'),
        };
        $key = $this->ids->generate().'.'.$extension;

        if (! Storage::disk(config('filesystems.cover_disk'))->put($this->path($key), $contents, ['ContentType' => $mimeType])) {
            throw new RuntimeException('Cover storage failed.');
        }

        return $key;
    }

    public function retrieve(string $key): ?StoredCover
    {
        $path = $this->path($key);
        $disk = Storage::disk(config('filesystems.cover_disk'));

        if (! $disk->exists($path)) {
            return null;
        }

        $contents = $disk->get($path);
        $mimeType = $disk->mimeType($path);

        if (! is_string($contents) || ! in_array($mimeType, ['image/jpeg', 'image/png'], true)) {
            return null;
        }

        return new StoredCover($contents, $mimeType);
    }

    public function delete(string $key): void
    {
        Storage::disk(config('filesystems.cover_disk'))->delete($this->path($key));
    }

    private function path(string $key): string
    {
        if (! preg_match('/\A[0-9a-f-]{36}\.(jpg|png)\z/i', $key)) {
            throw new RuntimeException('Invalid cover storage key.');
        }

        return 'covers/'.$key;
    }
}
