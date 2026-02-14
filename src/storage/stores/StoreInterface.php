<?php

namespace Arbor\storage\stores;


use Arbor\storage\streams\StreamInterface;


interface StoreInterface
{
    public function read(string $path): StreamInterface;

    public function write(string $path, StreamInterface $data): void;

    public function delete(string $path): void;

    public function list(string $path): array;

    public function rename(): void;

    public function exists(): bool;
}
