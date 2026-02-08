<?php

namespace Arbor\files\stores;


use Arbor\files\FileContext;


interface FileStoreInterface
{
    public function write(FileContext $context, string $path): void;

    public function read(string $path): mixed;

    public function exists(string $path): bool;

    public function delete(string $path): void;

    public function key(): string;
}
