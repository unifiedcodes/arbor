<?php

namespace Arbor\storage\streams;


interface StreamInterface
{
    public function resource();

    public function read(int $length): string;

    public function write(string $bytes): int;

    public function eof(): bool;

    public function close(): void;

    public function isReadable(): bool;

    public function isWritable(): bool;
}
