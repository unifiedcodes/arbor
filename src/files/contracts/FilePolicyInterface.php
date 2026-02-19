<?php

namespace Arbor\files\contracts;


interface FilePolicyInterface
{
    public function scheme(): string;

    public function mimes(): array;

    public function withOptions(array $options): static;
}
