<?php

namespace Arbor\files\strategies;

use Arbor\files\ingress\FileContext;
use Arbor\files\ingress\IngressNormalizer;
use RuntimeException;
use finfo;

final class ImageWithGD implements FileStrategyInterface
{
    private const MAX_SIZE = 5_000_000; // 5 MB

    private const ALLOWED_MIME = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];


    public function prove(FileContext $context): FileContext
    {
        // ---- claimed checks ----
        $size = $context->claimSize();

        if ($size <= 0 || $size > self::MAX_SIZE) {
            throw new RuntimeException('Invalid image size');
        }

        // ---- resolve source path ----
        $path = $context->materialize();


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
        return $context->normalize(
            mime: $mime,
            extension: self::ALLOWED_MIME[$mime],
            size: filesize($safePath),
            hash: hash_file('sha256', $safePath),
            binary: true,
            path: $safePath,
        );
    }
}
