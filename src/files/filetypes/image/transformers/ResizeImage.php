<?php

namespace Arbor\files\filetypes\image\transformers;


use Arbor\files\contracts\FileTransformerInterface;
use Arbor\files\state\FileContext;
use Arbor\files\Hydrator;
use Arbor\stream\StreamFactory;
use RuntimeException;
use GdImage;


final class ResizeImage implements FileTransformerInterface
{
    public function __construct(
        private readonly int $width,
        private readonly int $height,
        private readonly bool $preserveAspectRatio = true
    ) {}


    public function transform(FileContext $context): FileContext
    {
        $context = Hydrator::ensurePath($context);
        $sourcePath = $context->path();

        $info = getimagesize($sourcePath);

        if ($info === false) {
            throw new RuntimeException('Invalid image file.');
        }

        [$originalWidth, $originalHeight, $type] = $info;

        if ($originalWidth <= 0 || $originalHeight <= 0) {
            throw new RuntimeException('Invalid image dimensions.');
        }

        [$newWidth, $newHeight] = $this->calculateDimensions(
            $originalWidth,
            $originalHeight
        );

        $source = $this->createSourceImage($sourcePath, $type);

        $canvas = imagecreatetruecolor($newWidth, $newHeight);

        if (!$canvas instanceof GdImage) {
            throw new RuntimeException('Failed to create image canvas.');
        }

        $this->preserveTransparency($canvas, $type);

        imagecopyresampled(
            $canvas,
            $source,
            0,
            0,
            0,
            0,
            $newWidth,
            $newHeight,
            $originalWidth,
            $originalHeight
        );

        $binary = $this->renderToString($canvas, $type);

        $stream = StreamFactory::fromString($binary);

        return new FileContext(
            stream: $stream,
            path: null,
            name: $context->name(),
            extension: $context->inspectExtension(),
            mime: $context->inspectMime(),
            size: strlen($binary),
            isBinary: true,
            hash: null,
            proved: $context->isProved(),
            metadata: $context->metadata(),
        );
    }


    private function calculateDimensions(int $width, int $height): array
    {
        if (!$this->preserveAspectRatio) {
            return [$this->width, $this->height];
        }

        $ratio = min(
            $this->width / $width,
            $this->height / $height
        );

        return [
            (int) round($width * $ratio),
            (int) round($height * $ratio),
        ];
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

    private function preserveTransparency(GdImage $image, int $type): void
    {
        if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_WEBP) {
            imagealphablending($image, false);
            imagesavealpha($image, true);

            $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
            imagefill($image, 0, 0, $transparent);
        }
    }

    private function renderToString(GdImage $image, int $type): string
    {
        ob_start();

        $success = match ($type) {
            IMAGETYPE_JPEG => imagejpeg($image, null, 85),
            IMAGETYPE_PNG  => imagepng($image),
            IMAGETYPE_WEBP => imagewebp($image, null, 85),
            default => false,
        };

        if (!$success) {
            ob_end_clean();
            throw new RuntimeException('Failed to render image.');
        }

        return (string) ob_get_clean();
    }
}
