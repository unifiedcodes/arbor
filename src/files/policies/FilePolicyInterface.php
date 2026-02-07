<?php

namespace Arbor\files\policy;


use Arbor\files\FileContext;
use Arbor\files\filters\FileFilterInterface;
use Arbor\files\stores\FileStoreInterface;
use Arbor\files\strategies\FileStrategyInterface;


interface FilePolicyInterface
{
    /**
     * Which file strategies are allowed for this policy.
     * Order matters: first successful proof wins.
     *
     * @return array<class-string>
     */
    public function strategy(FileContext $context): FileStrategyInterface;

    /**
     * Policy checks to apply AFTER strategy proof + normalization.
     * Filters may throw to reject the file.
     *
     * @return FileFilterInterface[]
     */
    public function filters(FileContext $context): array;

    /**
     * Transformers to derive variants from the normalized file.
     * Key = variant name (used in file ref suffix).
     *
     * @return array<string, object>  // object implements FileTransformerInterface
     */
    public function transformers(FileContext $context): array;

    /**
     * Store to persist this file (and its variants).
     * Must return a concrete store instance.
     */
    public function store(FileContext $context): FileStoreInterface;

    /**
     * Optional logical namespace for storage paths
     * (e.g. avatars, banners, documents).
     */
    public function namespace(): string;


    // return supported mimes
    public function mimes(): array;
}
