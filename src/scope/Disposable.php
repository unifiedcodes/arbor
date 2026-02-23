<?php

namespace Arbor\scope;

/**
 * Represents a resource that must release
 * its internal resources when a scope ends.
 */
interface Disposable
{
    /**
     * Release any held resources.
     *
     * Must be idempotent.
     *
     * @return void
     */
    public function dispose(): void;
}
