<?php

namespace Arbor\files\strategies;

use Vips\Image as VipsImage;
use Arbor\files\FileContext;
use Arbor\files\FileNormalized;
use RuntimeException;


/**
 * libvips-based image strategy (Linux only)
 */
final class ImageStrategyVips implements FileStrategyInterface
{
    private const MAX_SIZE = 10_000_000; // 10 MB

    private const ALLOWED_MIME = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/avif' => 'avif',
    ];

    public function __construct()
    {
        $this->assertLinux();
        $this->assertVipsInstalled();
    }

    public function type(): string
    {
        return 'image';
    }

    /**
     * Hard validation
     */
    public function prove(FileContext $context): FileContext
    {
        $payload = $context->payload();

        // Size check
        $size = $context->get('size');
        if ($size <= 0 || $size > self::MAX_SIZE) {
            throw new RuntimeException('Invalid image size');
        }

        $path = $this->resolvePath($payload->source);

        // libvips header-only probe (no full decode)
        try {
            $image = VipsImage::newFromFile(
                $path,
                ['access' => 'sequential']
            );
        } catch (\Throwable $e) {
            throw new RuntimeException('File is not a valid image', 0, $e);
        }

        $mime = $image->get('mime-type') ?? null;

        if (!isset(self::ALLOWED_MIME[$mime])) {
            throw new RuntimeException('Unsupported or unsafe image format');
        }

        return $context
            ->with('trusted_mime', $mime)
            ->with('width', $image->width)
            ->with('height', $image->height)
            ->markProved();
    }

    /**
     * Security boundary
     * Decode → canonical re-encode
     */
    public function normalize(FileContext $context): FileContext
    {
        $payload = $context->payload();
        $mime    = $context->get('trusted_mime');

        if (!$mime) {
            throw new RuntimeException('Image not proven before normalization');
        }

        $path = $this->resolvePath($payload->source);

        try {
            $image = VipsImage::newFromFile(
                $path,
                ['access' => 'sequential']
            );
        } catch (\Throwable $e) {
            throw new RuntimeException('Failed to decode image', 0, $e);
        }

        // Canonical output
        $safePath = tempnam(sys_get_temp_dir(), 'vips_');
        $ext      = self::ALLOWED_MIME[$mime];

        $image->writeToFile(
            $safePath,
            [
                'strip' => true,     // remove metadata
                'Q'     => 85,       // sane default
            ]
        );

        return $context->withNormalized(
            new FileNormalized(
                path: $safePath,
                mime: $mime,
                extension: $ext
            )
        );
    }

    /**
     * ---- internal guards ----
     */

    private function assertLinux(): void
    {
        if (PHP_OS_FAMILY !== 'Linux') {
            throw new RuntimeException(
                'ImageStrategyVips is supported only on Linux'
            );
        }
    }

    private function assertVipsInstalled(): void
    {
        if (!class_exists(VipsImage::class)) {
            throw new RuntimeException(
                'libvips PHP bindings not installed. ' .
                    'Run: composer require jcupitt/vips'
            );
        }
    }

    private function resolvePath(mixed $source): string
    {
        if (is_string($source)) {
            return $source;
        }

        if (method_exists($source, 'getMetadata')) {
            $meta = $source->getMetadata();
            if (!empty($meta['uri'])) {
                return $meta['uri'];
            }
        }

        throw new RuntimeException('Unresolvable file source');
    }
}
