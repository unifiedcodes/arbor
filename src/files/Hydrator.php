<?php

namespace Arbor\files;

use Arbor\files\state\FileContext;
use Arbor\files\state\Payload;
use Arbor\stream\StreamInterface;
use Arbor\storage\Stats;
use Arbor\stream\StreamFactory;
use Arbor\facades\Storage;

use RuntimeException;


final class Hydrator
{
    public static function fromPayload(Payload $payload): FileContext
    {
        [$name, $extension] = self::normalizeName(
            $payload->name,
            $payload->extension
        );

        // creates unsafe filecontext.
        return new FileContext(
            stream: $payload->stream,
            path: $payload->path,
            name: $name,
            extension: $extension,
            mime: $payload->mime,
            size: $payload->size,
            isBinary: null,
            hash: $payload->hash,
            proved: false,
            metadata: []
        );
    }


    public static function prove(
        FileContext $context,
        ?string $mime = null,
        ?string $extension = null,
        ?int $size = null,
        ?bool $isBinary = null,
        ?string $hash = null,
        ?string $name = null,
        ?StreamInterface $stream = null,
        ?string $path = null,
        ?array $metadata = null
    ): FileContext {

        $ctx = $context;

        if ($ctx->isProved()) {
            throw new RuntimeException(
                'FileContext already proved.'
            );
        }

        // creates safe file context.

        $resolvedStream = self::resolve($stream, $ctx->stream());
        $resolvedPath = self::resolve($path, $ctx->path());

        if ($resolvedStream === null && $resolvedPath === null) {
            throw new RuntimeException(
                'Cannot prove FileContext: stream or path required.'
            );
        }

        [$resolvedName, $resolvedExtension] = self::normalizeName(
            self::resolve($name, $ctx->name()),
            self::resolve($extension, $ctx->inspectExtension())
        );

        return new FileContext(
            stream: $resolvedStream,
            path: $resolvedPath,
            name: $resolvedName,
            extension: $resolvedExtension,
            mime: self::require($mime, $ctx->inspectMime(), 'mime'),
            size: self::require($size, $ctx->inspectSize(), 'size'),
            isBinary: self::require($isBinary, $ctx->inspectBinary(), 'isBinary'),
            hash: self::resolve($hash, $ctx->hash()),
            proved: true,
            metadata: self::resolve($metadata, $ctx->metadata()),
        );
    }


    private static function resolve(mixed $override, mixed $fallback): mixed
    {
        return $override ?? $fallback;
    }


    private static function require(
        mixed $override,
        mixed $fallback,
        string $field
    ): mixed {
        $value = self::resolve($override, $fallback);

        if ($value === null) {
            throw new RuntimeException(
                "Cannot prove FileContext: missing verified {$field}."
            );
        }

        return $value;
    }


    public static function fromFileStat(Stats $stat): FileContext
    {
        $extension = self::require($stat->extension, null, 'extension');
        $name = self::require($stat->name, null, 'name');

        [$name, $extension] = self::normalizeName(
            $name,
            $extension
        );

        return new FileContext(
            stream: null,
            path: self::require($stat->path, null, 'path'),
            mime: self::require($stat->mime, null, 'mime'),
            name: $name,
            extension: $extension,
            size: self::require($stat->size, null, 'size'),
            isBinary: self::require($stat->binary, null, 'isBinary'),
            hash: null,
            proved: true,
            metadata: [
                'storage.type' => $stat->type,
                'storage.modified' => $stat->modified,
                'storage.created' => $stat->created,
                'storage.accessed' => $stat->accessed,
                'storage.permissions' => $stat->permissions,
                'storage.inode' => $stat->inode,
            ]
        );
    }


    public static function ensureStream(FileContext $context): FileContext
    {
        if ($context->stream() !== null) {
            return $context;
        }

        $path = $context->path();

        $stream = StreamFactory::fromFile($path);

        return new FileContext(
            stream: $stream,
            path: $path,
            name: $context->name(),
            mime: $context->inspectMime(),
            extension: $context->inspectExtension(),
            size: $context->inspectSize(),
            isBinary: $context->inspectBinary(),
            hash: $context->hash(),
            proved: $context->isProved(),
            metadata: $context->metadata(),
        );
    }


    public static function ensurePath(FileContext $context): FileContext
    {
        if ($context->path() !== null) {
            return $context;
        }

        $stream = $context->stream();

        $uri = Storage::writeTemp($stream);

        $path = Storage::absolutePath($uri);

        if ($path === '') {
            throw new RuntimeException('Failed to materialize stream to temporary storage.');
        }

        return new FileContext(
            stream: $stream,
            path: $path,
            name: $context->name(),
            mime: $context->inspectMime(),
            extension: $context->inspectExtension(),
            size: $context->inspectSize(),
            isBinary: $context->inspectBinary(),
            hash: $context->hash(),
            proved: $context->isProved(),
            metadata: $context->metadata(),
        );
    }


    private static function normalizeName(
        ?string $rawName,
        ?string $rawExtension
    ): array {
        if ($rawName === null && $rawExtension === null) {
            return [null, null];
        }

        // If name exists, extract from it
        if ($rawName !== null) {
            $info = pathinfo($rawName);

            $name = $info['filename'] ?? $rawName;
            $extensionFromName = $info['extension'] ?? null;

            // Prefer explicit extension override if provided
            $extension = $rawExtension ?? $extensionFromName;

            if ($extension !== null) {
                $extension = ltrim($extension, '.');
            }

            return [$name, $extension];
        }

        // If only extension exists
        return [null, ltrim($rawExtension, '.')];
    }
}
