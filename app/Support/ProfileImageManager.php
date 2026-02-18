<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfileImageManager
{
    private const DIRECTORY = 'profile-images';
    private const DEFAULT_IMAGE_PATH = 'images/default-avatar.svg';
    private const MAX_DIMENSION = 720;
    private const WEBP_QUALITY = 82;

    public static function store(UploadedFile $file): string
    {
        if (! self::canOptimize($file)) {
            return (string) $file->store(self::DIRECTORY, 'public');
        }

        $optimizedPath = self::createOptimizedWebp($file);
        if (! $optimizedPath) {
            return (string) $file->store(self::DIRECTORY, 'public');
        }

        try {
            $contents = @file_get_contents($optimizedPath);
            if ($contents === false) {
                return (string) $file->store(self::DIRECTORY, 'public');
            }

            $filename = Str::uuid()->toString().'.webp';
            Storage::disk('public')->put(self::DIRECTORY.'/'.$filename, $contents);

            return self::DIRECTORY.'/'.$filename;
        } finally {
            @unlink($optimizedPath);
        }
    }

    public static function delete(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }

    public static function url(?string $path): string
    {
        if ($path) {
            return Storage::disk('public')->url($path);
        }

        return asset(self::DEFAULT_IMAGE_PATH);
    }

    private static function canOptimize(UploadedFile $file): bool
    {
        if (! extension_loaded('gd')) {
            return false;
        }

        return in_array(
            (string) $file->getMimeType(),
            ['image/jpeg', 'image/png', 'image/webp'],
            true
        );
    }

    private static function createOptimizedWebp(UploadedFile $file): ?string
    {
        $sourcePath = $file->getRealPath();
        if (! is_string($sourcePath) || $sourcePath === '') {
            return null;
        }

        $binary = @file_get_contents($sourcePath);
        if ($binary === false) {
            return null;
        }

        $sourceImage = @imagecreatefromstring($binary);
        if (! $sourceImage) {
            return null;
        }

        try {
            $width = imagesx($sourceImage);
            $height = imagesy($sourceImage);

            if ($width <= 0 || $height <= 0) {
                return null;
            }

            [$targetWidth, $targetHeight] = self::constrainDimensions($width, $height, self::MAX_DIMENSION);

            $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
            if (! $canvas) {
                return null;
            }

            try {
                imagealphablending($canvas, true);
                imagesavealpha($canvas, true);

                imagecopyresampled(
                    $canvas,
                    $sourceImage,
                    0,
                    0,
                    0,
                    0,
                    $targetWidth,
                    $targetHeight,
                    $width,
                    $height
                );

                $tempBasePath = tempnam(sys_get_temp_dir(), 'profile_image_');
                if ($tempBasePath === false) {
                    return null;
                }

                @unlink($tempBasePath);
                $tempWebpPath = $tempBasePath.'.webp';

                if (! @imagewebp($canvas, $tempWebpPath, self::WEBP_QUALITY)) {
                    @unlink($tempWebpPath);

                    return null;
                }

                return $tempWebpPath;
            } finally {
                imagedestroy($canvas);
            }
        } finally {
            imagedestroy($sourceImage);
        }
    }

    /**
     * @return array{0:int,1:int}
     */
    private static function constrainDimensions(int $width, int $height, int $maxDimension): array
    {
        if ($width <= $maxDimension && $height <= $maxDimension) {
            return [$width, $height];
        }

        if ($width >= $height) {
            $targetWidth = $maxDimension;
            $targetHeight = (int) max(1, round(($height / $width) * $maxDimension));

            return [$targetWidth, $targetHeight];
        }

        $targetHeight = $maxDimension;
        $targetWidth = (int) max(1, round(($width / $height) * $maxDimension));

        return [$targetWidth, $targetHeight];
    }
}
