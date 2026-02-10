<?php

namespace Arbor\files\stores;

use Arbor\files\ingress\FileContext;
use RuntimeException;

final class LocalStore implements FileStoreInterface
{
    public function __construct(
        public readonly string $rootURL,
        public readonly string $rootDir
    ) {}


    public function write(FileContext $context, string $path): void
    {
        ensureDir($path);

        $source = $context->path();

        $fileName = $path . $context->name();

        if (!copy($source, $fileName)) {
            throw new RuntimeException(
                'Failed to write file to ' . $this->key() . ' store: ' . $path
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
        if (is_file($path) && !unlink($path)) {
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
