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
        // creates unsafe filecontext.
        return new FileContext(
            stream: $payload->stream,
            path: $payload->path,
            name: $payload->name,
            mime: $payload->mime,
            extension: $payload->extension,
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

        return new FileContext(
            stream: $resolvedStream,
            path: $resolvedPath,
            name: self::resolve($name, $ctx->name()),
            mime: self::require($mime, $ctx->inspectMime(), 'mime'),
            extension: self::require($extension, $ctx->inspectExtension(), 'extension'),
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
        return new FileContext(
            stream: null,
            path: self::require($stat->path, null, 'path'),
            name: self::require($stat->name, null, 'name'),
            mime: self::require($stat->mime, null, 'mime'),
            extension: self::require($stat->extension, null, 'extension'),
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

        $path = Storage::writeTemp($stream);

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
}
