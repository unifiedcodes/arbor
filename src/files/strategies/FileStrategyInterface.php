<?php

namespace Arbor\files\strategies;


use Arbor\files\FileContext;


interface FileStrategyInterface
{
    /**
     * Hard validation.
     * Must throw if file is NOT genuinely supported.
     */
    public function prove(FileContext $context): FileContext;

    /**
     * Security boundary.
     * Must normalize file into safe canonical form.
     */
    public function normalize(FileContext $context): FileContext;

    /**
     * Optional: trusted type label after proof
     */
    public function type(): string;
}
