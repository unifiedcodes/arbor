<?php

namespace Arbor\files;

use Arbor\files\state\FileContext;
use Arbor\files\state\Payload;
use Arbor\stream\StreamInterface;
use Arbor\storage\Stats;
use Arbor\stream\StreamFactory;
use Arbor\facades\Storage;

use RuntimeException;


/**
 * Stateless factory responsible for constructing and transitioning {@see FileContext}
 * instances throughout the file lifecycle.
 *
 * Hydrator centralises all FileContext construction logic, ensuring that contexts
 * are always built consistently regardless of their source. It handles four
 * distinct construction scenarios:
 *
 *  - {@see self::fromPayload()}: creates an unproved context from raw caller input.
 *  - {@see self::prove()}: transitions an unproved context into the proved state
 *    by resolving and validating all required metadata fields.
 *  - {@see self::fromFileStat()}: creates a proved context directly from a
 *    filesystem stat result, bypassing the prove step.
 *  - {@see self::ensureStream()} / {@see self::ensurePath()}: lazily materialise
 *    the missing source on an existing context without altering its proved state.
 *
 * @package Arbor\files
 */
final class Hydrator
{
    /**
     * Creates an unproved FileContext from a caller-supplied Payload.
     *
     * The resulting context carries whatever metadata the caller provided as hints
     * (MIME, size, extension) but is not yet in the proved state; a subsequent
     * call to {@see self::prove()} is required before the context can be used
     * with guarded accessors.
     *
     * @param Payload $payload The raw file payload produced by a {@see FileEntryInterface}.
     *
     * @return FileContext An unproved context populated with the payload's data.
     */
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


    /**
     * Transitions an unproved FileContext into the proved state.
     *
     * Each parameter acts as an override; when null, the corresponding value is
     * inherited from the existing context. The three required metadata fields —
     * mime, size, and isBinary — must resolve to a non-null value either from
     * the override or the context, otherwise an exception is thrown.
     *
     * The source invariant is also re-validated: at least one of stream or path
     * must be present on the resulting context.
     *
     * @param FileContext          $context   The unproved context to promote.
     * @param string|null          $mime      Verified MIME type override.
     * @param string|null          $extension Verified extension override (without leading dot).
     * @param int|null             $size      Verified file size override in bytes.
     * @param bool|null            $isBinary  Verified binary flag override.
     * @param string|null          $hash      Content hash override.
     * @param string|null          $name      Base filename override.
     * @param StreamInterface|null $stream    Stream source override.
     * @param string|null          $path      Filesystem path override.
     * @param array|null           $metadata  Metadata bag override; inherits existing bag if null.
     *
     * @return FileContext A new proved FileContext with all verified metadata set.
     *
     * @throws RuntimeException If the context is already proved.
     * @throws RuntimeException If neither stream nor path resolves to a non-null value.
     * @throws RuntimeException If any required metadata field (mime, size, isBinary) cannot be resolved.
     */
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


    /**
     * Resolves a value by preferring the override when non-null, falling back to
     * the existing value otherwise.
     *
     * @param mixed $override The preferred value; used when non-null.
     * @param mixed $fallback The fallback value used when $override is null.
     *
     * @return mixed The resolved value.
     */
    private static function resolve(mixed $override, mixed $fallback): mixed
    {
        return $override ?? $fallback;
    }


    /**
     * Resolves a required value and throws if the result is null.
     *
     * Behaves identically to {@see self::resolve()} but enforces that the
     * resolved value is non-null, making it suitable for fields that are
     * mandatory in the proved state.
     *
     * @param mixed  $override The preferred value; used when non-null.
     * @param mixed  $fallback The fallback value used when $override is null.
     * @param string $field    The field name included in the exception message on failure.
     *
     * @return mixed The resolved non-null value.
     *
     * @throws RuntimeException If both $override and $fallback are null.
     */
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


    /**
     * Creates a proved FileContext directly from a filesystem {@see Stats} result.
     *
     * All fields are sourced from the stat object; all required fields (path,
     * mime, name, extension, size, binary) must be non-null or an exception is
     * thrown. Storage-specific metadata (type, timestamps, permissions, inode) is
     * automatically populated into the context's metadata bag under the
     * "storage.*" namespace.
     *
     * @param Stats $stat A populated filesystem stat result.
     *
     * @return FileContext A proved FileContext hydrated from the stat data.
     *
     * @throws RuntimeException If any required stat field (path, mime, name, extension, size, binary) is null.
     */
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


    /**
     * Ensures the FileContext has a stream, opening one from the path if needed.
     *
     * If the context already has a stream, it is returned unchanged. Otherwise a
     * stream is opened from the context's path via {@see StreamFactory::fromFile()}
     * and a new context is returned with the stream set, preserving all other fields
     * and the current proved state.
     *
     * @param FileContext $context The context to ensure a stream on.
     *
     * @return FileContext The same context if a stream was already present, or a new
     *                     instance with the stream populated.
     */
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


    /**
     * Ensures the FileContext has a filesystem path, materialising one from the
     * stream if needed.
     *
     * If the context already has a path, it is returned unchanged. Otherwise the
     * stream is written to temporary storage via {@see Storage::writeTemp()} and
     * its absolute path is resolved. A new context is returned with both the
     * original stream and the resolved path set, preserving all other fields and
     * the current proved state.
     *
     * @param FileContext $context The context to ensure a path on.
     *
     * @return FileContext The same context if a path was already present, or a new
     *                     instance with the path populated.
     *
     * @throws RuntimeException If the stream cannot be materialised to temporary storage.
     */
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


    /**
     * Normalises a raw name and extension pair into a clean [name, extension] tuple.
     *
     * When a name is provided, pathinfo() is used to split it into its filename
     * stem and any embedded extension. An explicit $rawExtension override always
     * takes precedence over any extension inferred from the name. Leading dots are
     * stripped from the resolved extension. If neither argument is provided, [null,
     * null] is returned.
     *
     * @param string|null $rawName      The raw filename, which may include an extension (e.g. "photo.jpg").
     * @param string|null $rawExtension An explicit extension override, with or without a leading dot.
     *
     * @return array{0: string|null, 1: string|null} A tuple of [basename, extension], either of which may be null.
     */
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
