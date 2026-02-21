<?php

namespace Arbor\files\transformers;


use Arbor\files\contracts\FileTransformerInterface;
use Arbor\files\ingress\FileContext;
use Arbor\facades\Storage;
use Arbor\stream\StreamFactory;
use RuntimeException;
use GdImage;


final class ResizeImage implements FileTransformerInterface
{
    public function __construct(
        private int $maxWidth = 300,
        private int $maxHeight = 300
    ) {}

    public function transform(FileContext $context): FileContext
    {
        $sourcePath = $context->materialize();

        $info = getimagesize($sourcePath);

        if ($info === false) {
            throw new RuntimeException('Invalid image file.');
        }

        [$width, $height, $type] = $info;

        if ($width <= 0 || $height <= 0) {
            throw new RuntimeException('Invalid image dimensions.');
        }

        // ✅ Aspect ratio preserved
        $ratio = min(
            $this->maxWidth / $width,
            $this->maxHeight / $height,
            1 // no upscale
        );

        $newWidth  = (int) round($width * $ratio);
        $newHeight = (int) round($height * $ratio);

        $source = $this->createSourceImage($sourcePath, $type);

        $thumb = imagecreatetruecolor($newWidth, $newHeight);

        if (!$thumb instanceof GdImage) {
            throw new RuntimeException('Failed to create thumbnail.');
        }

        $this->preserveTransparency($thumb, $type);

        imagecopyresampled(
            $thumb,
            $source,
            0,
            0,
            0,
            0,
            $newWidth,
            $newHeight,
            $width,
            $height
        );

        $binary = $this->renderToString($thumb, $type);

        $thumbPath = $this->generateThumbPath($context->path());

        // Convert binary to Stream
        $stream = StreamFactory::fromString($binary);

        // Let Storage handle persistence
        Storage::write($thumbPath, $stream);

        return $context->withPath($thumbPath);
    }

    private function createSourceImage(string $path, int $type): GdImage
    {
        $image = match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($path),
            IMAGETYPE_PNG  => imagecreatefrompng($path),
            IMAGETYPE_WEBP => imagecreatefromwebp($path),
            default => throw new RuntimeException('Unsupported image type'),
        };

        if (!$image instanceof GdImage) {
            throw new RuntimeException('Failed to load image.');
        }

        return $image;
    }

    private function preserveTransparency(GdImage $thumb, int $type): void
    {
        if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_WEBP) {
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);

            $transparent = imagecolorallocatealpha($thumb, 0, 0, 0, 127);
            imagefill($thumb, 0, 0, $transparent);
        }
    }

    private function renderToString(GdImage $image, int $type): string
    {
        ob_start();

        $success = match ($type) {
            IMAGETYPE_JPEG => imagejpeg($image, null, 85),
            IMAGETYPE_PNG  => imagepng($image),
            IMAGETYPE_WEBP => imagewebp($image, null, 85),
        };

        if (!$success) {
            ob_end_clean();
            throw new RuntimeException('Failed to render image.');
        }

        return (string) ob_get_clean();
    }

    private function generateThumbPath(string $originalPath): string
    {
        $dir  = dirname($originalPath);
        $name = pathinfo($originalPath, PATHINFO_FILENAME);
        $ext  = pathinfo($originalPath, PATHINFO_EXTENSION);

        return $dir . '/' . $name . '_thumb.' . $ext;
    }
}
