<?php

namespace Arbor\files\stores;

use Arbor\files\FileContext;
use RuntimeException;

final class LocalStore implements FileStoreInterface
{

    public function write(FileContext $context, string $path): void
    {
        ensureDir(dirname($path));

        $source = $context->path();

        if (!@copy($source, $path)) {
            throw new RuntimeException(
                'Failed to write file to local store: ' . $path
            );
        }
    }


    public function read(string $path): mixed
    {
        if (!is_file($path)) {
            throw new RuntimeException(
                'File not found: ' . $path
            );
        }

        return file_get_contents($path);
    }


    public function exists(string $path): bool
    {
        return is_file($path);
    }


    public function delete(string $path): void
    {
        if (is_file($path) && !@unlink($path)) {
            throw new RuntimeException(
                'Failed to delete file: ' . $path
            );
        }
    }


    public function key(): string
    {
        return 'local';
    }
}
