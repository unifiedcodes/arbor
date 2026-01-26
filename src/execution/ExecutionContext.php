<?php

namespace Arbor\execution;


final class ExecutionContext
{
    private string $id;
    private ExecutionType $type;
    private float $startedAt;
    private ?string $parentId;

    public function __construct(
        ExecutionType $type,
        ?string $parentId = null,
        ?string $id = null,
        ?float $startedAt = null
    ) {
        $this->type      = $type;
        $this->parentId = $parentId;
        $this->id        = $id ?? self::generateId();
        $this->startedAt = $startedAt ?? microtime(true);
    }

    /* ---------- identity ---------- */

    public function id(): string
    {
        return $this->id;
    }

    public function parentId(): ?string
    {
        return $this->parentId;
    }

    /* ---------- classification ---------- */

    public function type(): ExecutionType
    {
        return $this->type;
    }

    public function isHttp(): bool
    {
        return $this->type === ExecutionType::HTTP;
    }

    public function isCli(): bool
    {
        return $this->type === ExecutionType::CLI;
    }

    public function isJob(): bool
    {
        return $this->type === ExecutionType::JOB;
    }

    /* ---------- timing ---------- */

    public function startedAt(): float
    {
        return $this->startedAt;
    }

    public function duration(): float
    {
        return microtime(true) - $this->startedAt;
    }

    /* ---------- helpers ---------- */

    private static function generateId(): string
    {
        // lightweight, no external deps
        return bin2hex(random_bytes(8));
    }
}
