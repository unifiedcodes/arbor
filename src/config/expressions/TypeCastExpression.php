<?php

namespace Arbor\config\expressions;

use Arbor\config\ResolverContext;

final class TypeCastExpression implements ExpressionInterface
{
    public function __construct(
        private readonly string $type,
        private readonly mixed $value,
    ) {}

    public function resolve(ResolverContext $ctx): mixed
    {
        // First resolve inner value (important for nested DSL/Expr)
        $resolved = $ctx->resolve($this->value);

        return $this->castValue($resolved);
    }

    private function castValue(mixed $value): mixed
    {
        return match ($this->type) {
            'string', 'str'  => (string) $value,
            'int', 'integer' => (int) $value,
            'float', 'double' => (float) $value,
            'bool', 'boolean' => (bool) $value,
            'array'          => (array) $value,
            'json'           => $this->castJson($value),

            default => throw new \InvalidArgumentException(
                "Unknown cast type '{$this->type}'"
            )
        };
    }

    private function castJson(mixed $value): mixed
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException("JSON cast failed: " . json_last_error_msg());
            }
            return $decoded;
        }

        // Convert non-strings to JSON if needed
        return json_decode(json_encode($value), true);
    }
}
