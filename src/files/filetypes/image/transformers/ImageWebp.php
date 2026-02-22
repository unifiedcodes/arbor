<?php

namespace Arbor\files\filetypes\image\transformers;

use Arbor\files\contracts\FileTransformerInterface;
use Arbor\files\state\FileContext;
use Arbor\files\Hydrator;
use Arbor\stream\StreamFactory;
use RuntimeException;
use GdImage;

/**
 * Transforms an image file into the WebP format using the GD extension.
 *
 * Accepts JPEG, PNG, and WebP source images and converts them to WebP,
 * preserving transparency where applicable. Returns a new {@see FileContext}
 * containing the converted image as a binary stream.
 *
 * @package Arbor\files\filetypes\image\transformers
 */
final class ImageWebp implements FileTransformerInterface
{
    /**
     * @param int $quality The WebP compression quality (0–100). Defaults to 85.
     */
    public function __construct(
        private readonly int $quality = 85
    ) {}

    /**
     * Convert the image described by the given file context into WebP format.
     *
     * Reads the source image from the resolved path, converts it to WebP using
     * the GD extension, and returns a new {@see FileContext} with the resulting
     * binary stream and updated metadata.
     *
     * @param  FileContext      $context The file context describing the source image.
     * @return FileContext               A new file context containing the WebP-converted image.
     * @throws RuntimeException          If GD WebP support is unavailable, the image is invalid,
     *                                   the dimensions are invalid, or the conversion fails.
     */
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

    /**
     * Create a GD image resource from the given file path and image type.
     *
     * Supports JPEG, PNG, and WebP source formats. Transparency is preserved
     * for PNG and WebP images by enabling alpha blending and saving the alpha channel.
     *
     * @param  string           $path The absolute path to the source image file.
     * @param  int              $type The image type constant (e.g., IMAGETYPE_JPEG).
     * @return GdImage               The loaded GD image resource.
     * @throws RuntimeException       If the image type is unsupported or the image fails to load.
     */
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
