<?php

namespace Arbor\files\filetypes\image\transformers;

use Arbor\files\contracts\FileTransformerInterface;
use Arbor\files\state\FileContext;
use Arbor\files\Hydrator;
use Arbor\stream\StreamFactory;
use RuntimeException;
use GdImage;

/**
 * Transforms an image file by resizing it to the specified dimensions using the GD extension.
 *
 * Supports JPEG, PNG, and WebP formats. When aspect ratio preservation is enabled,
 * the image is scaled to fit within the target dimensions without distortion.
 * Transparency is preserved for PNG and WebP images.
 *
 * @package Arbor\files\filetypes\image\transformers
 */
final class ResizeImage implements FileTransformerInterface
{
    /**
     * @param int  $width               The target width in pixels.
     * @param int  $height              The target height in pixels.
     * @param bool $preserveAspectRatio Whether to preserve the original aspect ratio. Defaults to true.
     */
    public function __construct(
        private readonly int $width,
        private readonly int $height,
        private readonly bool $preserveAspectRatio = true
    ) {}

    /**
     * Resize the image described by the given file context to the configured dimensions.
     *
     * Reads the source image from the resolved path, resamples it onto a new canvas
     * at the calculated dimensions, and returns a new {@see FileContext} containing
     * the resized image as a binary stream.
     *
     * @param  FileContext      $context The file context describing the source image.
     * @return FileContext               A new file context containing the resized image.
     * @throws RuntimeException          If the image is invalid, the dimensions are invalid,
     *                                   the canvas cannot be created, or rendering fails.
     */
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

    /**
     * Calculate the target dimensions based on the configured width, height,
     * and aspect ratio preservation setting.
     *
     * When aspect ratio preservation is enabled, the image is scaled proportionally
     * to fit within the target bounds using the smaller of the two scaling ratios.
     *
     * @param  int   $width  The original image width in pixels.
     * @param  int   $height The original image height in pixels.
     * @return array<int>    A two-element array containing the calculated [width, height].
     */
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

    /**
     * Create a GD image resource from the given file path and image type.
     *
     * Supports JPEG, PNG, and WebP source formats.
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

        return $image;
    }

    /**
     * Configure the canvas to preserve transparency for PNG and WebP images.
     *
     * Disables alpha blending, enables alpha saving, and fills the canvas
     * with a fully transparent color to ensure transparent pixels are retained.
     *
     * @param  GdImage $image The GD canvas to configure.
     * @param  int     $type  The image type constant (e.g., IMAGETYPE_PNG).
     * @return void
     */
    private function preserveTransparency(GdImage $image, int $type): void
    {
        if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_WEBP) {
            imagealphablending($image, false);
            imagesavealpha($image, true);

            $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
            imagefill($image, 0, 0, $transparent);
        }
    }

    /**
     * Render the GD image resource to a binary string in the appropriate format.
     *
     * Outputs the image to a buffer and captures the result as a string,
     * preserving the original image type.
     *
     * @param  GdImage         $image The GD image resource to render.
     * @param  int             $type  The image type constant determining the output format.
     * @return string                 The rendered image as a binary string.
     * @throws RuntimeException        If rendering fails for the given image type.
     */
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
