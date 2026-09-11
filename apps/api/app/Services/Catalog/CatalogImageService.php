<?php

namespace App\Services\Catalog;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\ProductVariantImage;
use App\Support\CatalogImagePath;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

final class CatalogImageService
{
    /**
     * @return array{path: string, thumbnail_path: string, url: string, thumbnail_url: string}
     */
    public function storeUploaded(UploadedFile $file, string $directory = 'catalog'): array
    {
        $directory = $directory === 'reviews' ? 'reviews' : 'catalog';
        $ulid = (string) Str::ulid();
        $extension = $this->extension($file);
        $path = $directory.'/'.$ulid.'.'.$extension;
        $thumbnailPath = $directory.'/'.$ulid.'_thumb.'.$extension;
        $disk = Storage::disk('public');

        $contents = file_get_contents($file->getRealPath());
        $disk->put($path, $contents === false ? '' : $contents);

        $manager = new ImageManager(new Driver);
        $image = $manager->decodePath($file->getRealPath());
        $image->scaleDown(400, 400);
        $encoded = $image->encodeUsingFileExtension($extension);
        $disk->put($thumbnailPath, (string) $encoded);

        return [
            'path' => $path,
            'thumbnail_path' => $thumbnailPath,
            'url' => CatalogImagePath::url($path),
            'thumbnail_url' => CatalogImagePath::url($thumbnailPath),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function attachProduct(Product $product, array $data): ProductImage
    {
        $isPrimary = (bool) ($data['is_primary'] ?? false);
        if (! $product->images()->exists()) {
            $isPrimary = true;
        }

        if ($isPrimary) {
            $product->images()->where('is_primary', true)->update(['is_primary' => false]);
        }

        return ProductImage::query()->create([
            'product_id' => $product->id,
            'path' => $data['path'],
            'alt' => $data['alt'] ?? null,
            'position' => $data['position'] ?? 0,
            'is_primary' => $isPrimary,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function attachVariant(ProductVariant $variant, array $data): ProductVariantImage
    {
        $isPrimary = (bool) ($data['is_primary'] ?? false);
        if (! $variant->images()->exists()) {
            $isPrimary = true;
        }

        if ($isPrimary) {
            $variant->images()->where('is_primary', true)->update(['is_primary' => false]);
        }

        return ProductVariantImage::query()->create([
            'product_variant_id' => $variant->id,
            'path' => $data['path'],
            'alt' => $data['alt'] ?? null,
            'position' => $data['position'] ?? 0,
            'is_primary' => $isPrimary,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateProductImage(ProductImage $image, array $data): ProductImage
    {
        $isPrimary = array_key_exists('is_primary', $data) ? (bool) $data['is_primary'] : $image->is_primary;
        if ($isPrimary && ! $image->is_primary) {
            ProductImage::query()
                ->where('product_id', $image->product_id)
                ->where('is_primary', true)
                ->update(['is_primary' => false]);
        }

        $image->fill([
            'alt' => array_key_exists('alt', $data) ? $data['alt'] : $image->alt,
            'position' => $data['position'] ?? $image->position,
            'is_primary' => $isPrimary,
        ])->save();

        return $image->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateVariantImage(ProductVariantImage $image, array $data): ProductVariantImage
    {
        $isPrimary = array_key_exists('is_primary', $data) ? (bool) $data['is_primary'] : $image->is_primary;
        if ($isPrimary && ! $image->is_primary) {
            ProductVariantImage::query()
                ->where('product_variant_id', $image->product_variant_id)
                ->where('is_primary', true)
                ->update(['is_primary' => false]);
        }

        $image->fill([
            'alt' => array_key_exists('alt', $data) ? $data['alt'] : $image->alt,
            'position' => $data['position'] ?? $image->position,
            'is_primary' => $isPrimary,
        ])->save();

        return $image->refresh();
    }

    public function softDeleteImage(ProductImage|ProductVariantImage $image): void
    {
        $image->delete();
    }

    private function extension(UploadedFile $file): string
    {
        $extension = strtolower((string) ($file->extension() ?: $file->guessExtension()));

        return match ($extension) {
            'jpeg', 'jpg' => 'jpg',
            'png' => 'png',
            'webp' => 'webp',
            default => 'jpg',
        };
    }
}
