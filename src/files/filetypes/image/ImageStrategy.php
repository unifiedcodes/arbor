<?php

namespace Arbor\files\filetypes\image;

use Arbor\files\contracts\FileStrategyInterface;
use Arbor\files\Hydrator;
use Arbor\files\state\FileContext;
use RuntimeException;
use finfo;

/**
 * Proves and sanitizes an image file context by performing multi-layered validation and re-encoding.
 *
 * Applies a security-focused pipeline to incoming image files, including size validation,
 * real MIME type detection, structural integrity checks, and a decode-and-re-encode pass
 * to strip potentially malicious payloads embedded in image files.
 *
 * Supports JPEG, PNG, and WebP formats with a maximum file size of 5 MB.
 *
 * @package Arbor\files\filetypes\image
 */
final class ImageStrategy implements FileStrategyInterface
{
    /**
     * Maximum permitted file size in bytes (5 MB).
     */
    private const MAX_SIZE = 5_000_000;

    /**
     * Map of allowed MIME types to their corresponding file extensions.
     *
     * @var array<string, string>
     */
    private const ALLOWED_MIME = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    /**
     * Prove the validity and safety of the given image file context.
     *
     * Executes the following validation and sanitization pipeline:
     * - Validates the claimed file size is within the permitted bounds.
     * - Resolves the source file path via the {@see Hydrator}.
     * - Detects the real MIME type using {@see finfo} to catch spoofed uploads.
     * - Validates the structural integrity of the image via {@see getimagesize()}.
     * - Decodes and re-encodes the image using GD to strip embedded payloads.
     * - Returns a normalized and proved {@see FileContext} pointing to the sanitized file.
     *
     * @param  FileContext      $context The file context describing the incoming image.
     * @return FileContext               A new, proved file context referencing the sanitized image.
     * @throws RuntimeException          If the file size is invalid, the MIME type is unsupported or spoofed,
     *                                   the image structure is invalid, or the image fails to decode.
     */
    public function prove(FileContext $context): FileContext
    {
        // ---- claimed checks ----
        $size = $context->inspectSize();

        if ($size <= 0 || $size > self::MAX_SIZE) {
            throw new RuntimeException('Invalid image size');
        }

        // ---- resolve source path ----
        $context = Hydrator::ensurePath($context);
        $path = $context->path();

        // ---- real MIME detection ----
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($path);

        if (!isset(self::ALLOWED_MIME[$mime])) {
            throw new RuntimeException('Unsupported or spoofed image type');
        }

        // ---- structural image validation ----
        $info = @getimagesize($path);
        if ($info === false) {
            throw new RuntimeException('Invalid image structure');
        }

        // ---- security boundary: decode & re-encode ----
        $image = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png'  => @imagecreatefrompng($path),
            'image/webp' => @imagecreatefromwebp($path),
            default      => null,
        };

        if (!$image) {
            throw new RuntimeException('Failed to decode image');
        }

        $safePath = tempnam(sys_get_temp_dir(), 'img_');

        match ($mime) {
            'image/jpeg' => imagejpeg($image, $safePath, 90),
            'image/png'  => imagepng($image, $safePath),
            'image/webp' => imagewebp($image, $safePath, 85),
        };

        // ---- final normalization ----
        return Hydrator::prove(
            context: $context,
            mime: $mime,
            extension: self::ALLOWED_MIME[$mime],
            size: filesize($safePath),
            isBinary: true,
            path: $safePath,
        );
    }
}
