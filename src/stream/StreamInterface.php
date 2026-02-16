<?php

namespace Arbor\stream;


interface StreamInterface
{
    // base reading
    public function read(int $length): string;
    public function eof(): bool;

    // writing capability (optional, but allowed)
    public function write(string $bytes): int;
    public function isReadable(): bool;
    public function isWritable(): bool;

    // seek / replay capability
    public function isSeekable(): bool;
    public function rewind(): void;

    // lifecycle
    public function close(): void;
    public function detach();
    public function tell();
}
