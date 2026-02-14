<?php

namespace Arbor\files\record;


use LogicException;
use Arbor\files\ingress\FileContext;


final class FileRecord
{
    private function __construct(
        public readonly string $store,
        public readonly string $path,
        public readonly string $uri,
        public readonly string $publicURL,
        public readonly string $namespace,

        public readonly string $mime,
        public readonly string $extension,
        public readonly int $size,
        public readonly string $hash,

        public readonly string $name,
    ) {}

    public static function from(
        FileContext $context,
        string $storeKey,
        string $path,
        string $publicURL,
        string $namespace = '',
    ): self {
        if (!$context->isProved()) {
            throw new LogicException(
                'Cannot create FileRecord from unproved FileContext'
            );
        }

        return new self(
            store: $storeKey,
            path: $path,
            uri: self::generateURI($storeKey, $context, $namespace),
            publicURL: $publicURL,

            mime: $context->mime(),
            extension: $context->extension(),
            size: $context->size(),
            hash: $context->hash(),

            name: $context->name(),
            namespace: $namespace
        );
    }

    public static function generateURI(
        string $store,
        FileContext $context,
        string $namespace = '',
        ?string $variant = null,
    ): string {
        $store = rtrim($store, '/');
        $namespace = trim($namespace, '/');
        $name = $context->name();

        if ($variant !== null) {
            $name = self::applyVariant($name, $variant);
        }

        return $namespace === ''
            ? "{$store}://{$name}"
            : "{$store}://{$namespace}/{$name}";
    }


    private static function applyVariant(string $filename, string $variant): string
    {
        $dot = strrpos($filename, '.');

        if ($dot === false) {
            return $filename . '~' . $variant;
        }

        return substr($filename, 0, $dot)
            . '~' . $variant
            . substr($filename, $dot);
    }


    public static function parseURI(string $uri): array
    {
        $parts = parse_url($uri);

        if ($parts === false || !isset($parts['scheme'])) {
            throw new LogicException("Invalid file URI: {$uri}");
        }

        $store = $parts['scheme'];
        $namespace = $parts['host'] ?? '';

        $path = ltrim($parts['path'] ?? '', '/');

        if ($path === '') {
            throw new LogicException("File URI has no target path: {$uri}");
        }

        $variant = null;
        $name = $path;

        if (str_contains($path, '~')) {
            [$name, $variant] = explode('~', $path, 2);
        }

        return [
            'store'     => $store,
            'namespace' => $namespace,
            'name'      => $name,
            'variant'   => $variant,
        ];
    }
}
