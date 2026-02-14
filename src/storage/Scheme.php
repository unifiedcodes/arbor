<?php

namespace Arbor\storage;


use Arbor\storage\stores\StoreInterface;


final class Scheme
{
    public function __construct(
        private string $name,
        private StoreInterface $store,
        private string $root = '',
        private ?string $baseUrl = null,
        private bool $public = false
    ) {}


    public function name(): string
    {
        return $this->name;
    }


    public function store(): StoreInterface
    {
        return $this->store;
    }


    public function isPublic(): bool
    {
        return $this->public;
    }


    public function baseUrl(): ?string
    {
        return $this->baseUrl;
    }


    public function root(): string
    {
        return $this->root;
    }
}
