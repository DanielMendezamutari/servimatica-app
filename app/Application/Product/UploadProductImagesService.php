<?php

namespace App\Application\Product;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadProductImagesService
{
    /**
     * Store main cover image and return relative path
     */
    public function storeCover(UploadedFile $file): string
    {
        $dir = storage_path('app/public/products');
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
            @chmod($dir, 0777);
        }
        $filename = 'prod_' . Str::random(20) . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('products', $filename, 'public');
        @chmod(storage_path('app/public/' . $path), 0777);
        return $path;
    }

    /**
     * Store an array of gallery images and return array of relative paths
     *
     * @param UploadedFile[] $files
     * @return string[]
     */
    public function storeGallery(array $files): array
    {
        $dir = storage_path('app/public/products/gallery');
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
            @chmod($dir, 0777);
        }
        $paths = [];
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $filename = 'gallery_' . Str::random(20) . '.' . $file->getClientOriginalExtension();
                $storedPath = $file->storeAs('products/gallery', $filename, 'public');
                @chmod(storage_path('app/public/' . $storedPath), 0777);
                $paths[] = $storedPath;
            }
        }
        return $paths;
    }

    /**
     * Delete files from public disk
     */
    public function deleteFiles(string|array $paths): void
    {
        $paths = is_array($paths) ? $paths : [$paths];
        foreach ($paths as $path) {
            if (!empty($path) && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }
    }
}
