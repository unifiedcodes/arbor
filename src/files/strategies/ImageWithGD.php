<?php

namespace Arbor\files\strategies;

use Arbor\files\FileContext;
use Arbor\files\FileNormalized;
use RuntimeException;
use finfo;


final class ImageWithGD implements FileStrategyInterface
{
    private const MAX_SIZE = 5_000_000; // 10 MB

    private const ALLOWED_MIME = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

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

        // 1. Size limit
        if ($context->get('size') <= 0 || $context->get('size') > self::MAX_SIZE) {
            throw new RuntimeException('Invalid image size');
        }

        // 2. Resolve source path safely
        $path = $this->resolvePath($payload->source);

        // 3. Real MIME detection (server-side)
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $realMime = $finfo->file($path);

        if (!isset(self::ALLOWED_MIME[$realMime])) {
            throw new RuntimeException('Unsupported or spoofed image type');
        }

        // 4. Image header validation (no full read)
        $info = @getimagesize($path);
        if ($info === false) {
            throw new RuntimeException('Invalid image structure');
        }

        return $context
            ->with('trusted_mime', $realMime)
            ->with('width',  $info[0])
            ->with('height', $info[1])
            ->markProved();
    }

    /**
     * Security boundary
     * Decode → re-encode → discard original
     */
    public function normalize(FileContext $context): FileContext
    {
        $payload = $context->payload();
        $mime    = $context->get('trusted_mime');

        $path = $this->resolvePath($payload->source);

        $image = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png'  => @imagecreatefrompng($path),
            'image/webp' => @imagecreatefromwebp($path),
            default      => null,
        };

        if (!$image) {
            throw new RuntimeException('Failed to decode image');
        }

        // Canonical output (always safe)
        $safePath = tempnam(sys_get_temp_dir(), 'img_');

        match ($mime) {
            'image/jpeg' => imagejpeg($image, $safePath, 90),
            'image/png'  => imagepng($image, $safePath),
            'image/webp' => imagewebp($image, $safePath, 85),
        };


        return $context->withNormalized(
            new FileNormalized(
                path: $safePath,
                mime: $context->get('trusted_mime'),
                extension: self::ALLOWED_MIME[$context->get('trusted_mime')]
            )
        );
    }

    /**
     * Resolve stream or path safely
     */
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
