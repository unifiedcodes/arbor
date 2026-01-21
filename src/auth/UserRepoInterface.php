<?php

namespace Arbor\auth;


interface UserRepoInterface
{
    public function findById(string $id): ?array;
    public function findByIdentifier(string $identifier): ?array;
    public function getPasswordHash(string $userId): ?string;
}
