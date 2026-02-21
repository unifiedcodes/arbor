<?php

namespace Arbor\files\filetypes\image\transformers;

use Arbor\files\contracts\FileTransformerInterface;
use Arbor\files\state\FileContext;
use Arbor\files\Hydrator;
use Arbor\stream\StreamFactory;
use RuntimeException;
use GdImage;


final class ImageWebp implements FileTransformerInterface
{
    public function __construct(
        private readonly int $quality = 85
    ) {}

    public function transform(FileContext $context): FileContext
    {
        if (!function_exists('imagewebp')) {
            throw new RuntimeException('GD WebP support is not enabled.');
        }

        $context = Hydrator::ensurePath($context);
        $sourcePath = $context->path();

        $info = getimagesize($sourcePath);

        if ($info === false) {
            throw new RuntimeException('Invalid image file.');
        }

        [$width, $height, $type] = $info;

        if ($width <= 0 || $height <= 0) {
            throw new RuntimeException('Invalid image dimensions.');
        }

        $source = $this->createSourceImage($sourcePath, $type);

        // Render to WebP
        ob_start();
        $success = imagewebp($source, null, $this->quality);

        if (!$success) {
            ob_end_clean();
            throw new RuntimeException('Failed to convert image to WebP.');
        }

        $binary = (string) ob_get_clean();

        $stream = StreamFactory::fromString($binary);

        return new FileContext(
            stream: $stream,
            path: null,
            name: $context->name(),
            extension: 'webp',
            mime: 'image/webp',
            size: strlen($binary),
            isBinary: true,
            hash: null,
            proved: $context->isProved(),
            metadata: $context->metadata(),
        );
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

        // Preserve transparency for PNG/WebP
        if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_WEBP) {
            imagealphablending($image, true);
            imagesavealpha($image, true);
        }

        return $image;
    }
}
