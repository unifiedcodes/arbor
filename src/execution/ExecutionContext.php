<?php

namespace Arbor\execution;


/**
 * Represents the context and metadata of an application execution.
 *
 * Encapsulates information about a single execution instance, including its type
 * (HTTP, CLI, or Job), unique identifier, timing information, and parent-child
 * relationships for nested executions.
 *
 * This class is immutable after construction and provides convenience methods
 * for execution type classification and timing calculations.
 */
final class ExecutionContext
{
    private string $id;
    private ExecutionType $type;
    private float $startedAt;
    private ?string $parentId;

    private ?string $baseURI;
    private ?string $basePath;

    /**
     * Creates a new execution context.
     *
     * @param ExecutionType $type The type of execution (HTTP, CLI, or JOB).
     * @param string|null $parentId Optional ID of the parent execution context for nested executions.
     * @param string|null $id Optional unique identifier. A random ID is generated if not provided.
     * @param float|null $startedAt Optional start time as a microtime float. Current time is used if not provided.
     */
    public function __construct(
        ExecutionType $type,
        ?string $baseURI = null,
        ?string $parentId = null,
        ?string $id = null,
        ?float $startedAt = null
    ) {
        $this->type      = $type;
        $this->parentId = $parentId;
        $this->id        = $id ?? self::generateId();
        $this->startedAt = $startedAt ?? microtime(true);
        $this->baseURI  = $baseURI;
        $this->basePath = $baseURI
            ? (parse_url($baseURI, PHP_URL_PATH) ?: '')
            : null;
    }

    /* ---------- identity ---------- */

    /**
     * Gets the unique identifier for this execution context.
     *
     * @return string The execution context ID.
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * Gets the ID of the parent execution context.
     *
     * @return string|null The parent execution context ID, or null if this is a root execution.
     */
    public function parentId(): ?string
    {
        return $this->parentId;
    }

    /* ---------- classification ---------- */

    /**
     * Gets the execution type.
     *
     * @return ExecutionType The type of execution (HTTP, CLI, or JOB).
     */
    public function type(): ExecutionType
    {
        return $this->type;
    }

    /**
     * Checks if this execution is from an HTTP request.
     *
     * @return bool True if the execution type is HTTP, false otherwise.
     */
    public function isHttp(): bool
    {
        return $this->type === ExecutionType::HTTP;
    }

    /**
     * Checks if this execution is from a CLI command.
     *
     * @return bool True if the execution type is CLI, false otherwise.
     */
    public function isCli(): bool
    {
        return $this->type === ExecutionType::CLI;
    }

    /**
     * Checks if this execution is a background job.
     *
     * @return bool True if the execution type is JOB, false otherwise.
     */
    public function isJob(): bool
    {
        return $this->type === ExecutionType::JOB;
    }

    /* ---------- timing ---------- */

    /**
     * Gets the start time of the execution.
     *
     * @return float The start time as a microtime float value.
     */
    public function startedAt(): float
    {
        return $this->startedAt;
    }

    /**
     * Gets the elapsed time since execution started.
     *
     * @return float The duration in seconds as a float, calculated from the current time.
     */
    public function duration(): float
    {
        return microtime(true) - $this->startedAt;
    }

    /* ---------- helpers ---------- */

    /**
     * Generates a random, lightweight unique identifier.
     *
     * Uses random_bytes() for cryptographic randomness and converts to hexadecimal
     * format. No external dependencies are required.
     *
     * @return string A 16-character hexadecimal string representing 8 random bytes.
     */
    private static function generateId(): string
    {
        // lightweight, no external deps
        return bin2hex(random_bytes(8));
    }


    /* ---------- HTTP environment ---------- */

    /**
     * Returns the base URI for this execution, if any.
     * Only applicable to HTTP executions.
     */
    public function baseURI(): ?string
    {
        return $this->baseURI;
    }

    /**
     * Returns the base path derived from base URI, if any.
     */
    public function basePath(): ?string
    {
        return $this->basePath;
    }
}
