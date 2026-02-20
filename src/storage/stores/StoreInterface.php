<?php

namespace Arbor\storage\stores;


use Arbor\stream\StreamInterface;


interface StoreInterface
{
    public function read(string $path): StreamInterface;

    public function write(string $path, StreamInterface $data): void;

    public function copy(string $from, string $to): void;

    public function delete(string $path): void;

    public function list(string $path): array;

    public function rename(string $from, string $to): void;

    public function append(string $path, StreamInterface $data): void;

    public function exists(string $path): bool;

    public function stats(string $path): array;
}
