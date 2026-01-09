<?php

namespace Arbor\router;

use Arbor\router\Node;
use Arbor\router\Meta;

final class RouteContext
{
    public function __construct(
        private string $path,
        private string $verb,
        private int $statusCode,
        private ?Node $node,
        private ?Meta $meta,
        private array $parameters,
        private string|array $handler,
        private array $middlewares,
    ) {}

    /* -----------------------------------------------------------------
     | Named constructors
     |-----------------------------------------------------------------*/

    public static function error(
        string $path,
        string $verb,
        int $statusCode,
        string|array $handler,
    ): self {
        return new self(
            path: $path,
            verb: $verb,
            statusCode: $statusCode,
            node: null,
            meta: null,
            parameters: [],
            handler: $handler,
            middlewares: [],
        );
    }

    /* -----------------------------------------------------------------
     | Identity
     |-----------------------------------------------------------------*/

    public function path(): string
    {
        return $this->path;
    }

    public function verb(): string
    {
        return $this->verb;
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public function isError(): bool
    {
        return $this->statusCode >= 400;
    }

    public function isSuccessful(): bool
    {
        return $this->statusCode < 400;
    }

    /* -----------------------------------------------------------------
     | Handler & middlewares
     |-----------------------------------------------------------------*/

    public function handler(): string|array
    {
        return $this->handler;
    }

    public function middlewares(): array
    {
        return $this->middlewares;
    }

    public function withMergedMiddlewares(array $middlewares): self
    {
        if (!$middlewares) {
            return $this;
        }

        $clone = clone $this;

        $clone->middlewares = array_values(array_unique(
            array_merge($middlewares, $this->middlewares)
        ));

        return $clone;
    }

    /* -----------------------------------------------------------------
     | Parameters
     |-----------------------------------------------------------------*/

    public function parameters(): array
    {
        return $this->parameters;
    }

    public function parameter(string $key, mixed $default = null): mixed
    {
        return $this->parameters[$key] ?? $default;
    }

    public function hasParameter(string $key): bool
    {
        return array_key_exists($key, $this->parameters);
    }

    /* -----------------------------------------------------------------
     | Meta & attributes
     |-----------------------------------------------------------------*/

    public function meta(): ?Meta
    {
        return $this->meta;
    }

    public function attributes(): array
    {
        return $this->meta?->attributes() ?? [];
    }

    public function attribute(string $key, mixed $default = null): mixed
    {
        return $this->meta?->getAttribute($key, $default);
    }

    public function hasAttribute(string $key): bool
    {
        return $this->meta?->hasAttribute($key) ?? false;
    }

    /* -----------------------------------------------------------------
     | Node & grouping
     |-----------------------------------------------------------------*/

    public function node(): ?Node
    {
        return $this->node;
    }

    public function groupId(): ?string
    {
        return $this->node?->getGroupId();
    }
}
