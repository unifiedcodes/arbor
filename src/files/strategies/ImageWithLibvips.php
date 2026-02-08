<?php

namespace Arbor\files\strategies;

use Arbor\files\FileContext;
use Arbor\files\FilePathResolver;
use Vips\Image as VipsImage;
use RuntimeException;

final class ImageStrategyVips implements FileStrategyInterface
{
    private const MAX_SIZE = 5_000_000; // 5 MB

    private const ALLOWED_MIME = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/avif' => 'avif',
    ];

    public function __construct()
    {
        if (!class_exists(VipsImage::class)) {
            throw new RuntimeException(
                'libvips PHP bindings not installed. ' .
                    'Run: composer require jcupitt/vips'
            );
        }
    }

    /**
     * Validate, canonicalize, and normalize image.
     * Single trust + commit point.
     */
    public function prove(FileContext $context): FileContext
    {
        // ---- claimed checks ----
        $claimedSize = $context->claimSize();
        if ($claimedSize <= 0 || $claimedSize > self::MAX_SIZE) {
            throw new RuntimeException('Invalid image size');
        }

        // ---- resolve readable path ----
        $source = $context->getPayload()->source;
        $path = FilePathResolver::resolve($source);

        // ---- header-only probe (cheap) ----
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

        // ---- security boundary: canonical re-encode ----
        $safePath = tempnam(sys_get_temp_dir(), 'vips_');
        $extension = self::ALLOWED_MIME[$mime];

        try {
            $image->writeToFile(
                $safePath,
                [
                    'strip' => true, // drop metadata
                    'Q'     => 85,
                ]
            );
        } catch (\Throwable $e) {
            throw new RuntimeException('Failed to normalize image', 0, $e);
        }

        // ---- finalize identity ----
        $finalSize = filesize($safePath);
        $hash      = hash_file('sha256', $safePath);

        return $context->normalize(
            mime: $mime,
            extension: $extension,
            size: $finalSize,
            path: $safePath,
            hash: $hash,
            binary: true,
        );
    }
}
