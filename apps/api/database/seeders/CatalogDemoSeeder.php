<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\StockItem;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class CatalogDemoSeeder extends Seeder
{
    public function run(): void
    {
        $brand = $this->brand();
        $categories = $this->categories();
        $size = $this->attribute('size', 'Kích thước', 1);
        $color = $this->attribute('color', 'Màu sắc', 2);

        $sizes = [
            'S' => $this->option($size, 'S', 1),
            'M' => $this->option($size, 'M', 2),
            'L' => $this->option($size, 'L', 3),
            'XL' => $this->option($size, 'XL', 4),
        ];
        $colors = [
            'Trắng' => $this->option($color, 'Trắng', 1),
            'Đen' => $this->option($color, 'Đen', 2),
            'Navy' => $this->option($color, 'Navy', 3),
            'Be' => $this->option($color, 'Be', 4),
        ];

        $warehouse = Warehouse::ensureDefault();

        foreach ($this->catalog() as $index => $item) {
            $this->seedProduct($item, $brand, $categories, $size, $color, $sizes, $colors, $warehouse, $index);
        }
    }

    /**
     * Assortment inspired by Vietnamese casual/streetwear (Yame-style mix).
     * Product photos are original studio/lifestyle stills, not third-party brand assets.
     *
     * @return list<array<string, mixed>>
     */
    private function catalog(): array
    {
        return [
            [
                'slug' => 'demo-tee',
                'sku_prefix' => 'DEMO-TEE',
                'name' => 'Áo Thun Oversize Cotton 220GSM',
                'category' => 'ao-thun',
                'description' => "Áo thun oversize cotton 220GSM, phom rộng dễ mặc hằng ngày.\nChất vải dày dặn, đứng form, phù hợp streetwear và đi làm casual.\nGiặt máy nhẹ, không cần ủi nhiều.",
                'price' => 299000,
                'compare' => 375000,
                'sizes' => ['S', 'M', 'L', 'XL'],
                'color' => 'Trắng',
                'images' => ['watch-look-white-tee.png', 'watch-tee-white.png', 'watch-tee-white-detail.png'],
            ],
            [
                'slug' => 'ao-thun-graphic-streetwear',
                'sku_prefix' => 'WATCH-TEE-GR',
                'name' => 'Áo Thun Graphic Streetwear',
                'category' => 'ao-thun',
                'description' => "Áo thun graphic tối giản, in tonal không phô.\nCotton compact, cổ rib bền, mặc layer với khoác denim hoặc hoodie.\nPhom regular hơi rộng vai.",
                'price' => 349000,
                'compare' => 429000,
                'sizes' => ['S', 'M', 'L'],
                'color' => 'Đen',
                'images' => ['watch-tee-black-life.png', 'watch-tee-black.png', 'watch-tee-black-detail.png'],
            ],
            [
                'slug' => 'ao-polo-det-kim-navy',
                'sku_prefix' => 'WATCH-POLO',
                'name' => 'Áo Polo Dệt Kim Regular',
                'category' => 'polo',
                'description' => "Polo dệt kim navy, cổ đứng, cài 2 nút.\nMặc đi làm hay cuối tuần đều gọn. Thấm hút tốt, không bóng.\nPhom regular, dễ phối quần jean hoặc kaki.",
                'price' => 248000,
                'compare' => 320000,
                'sizes' => ['S', 'M', 'L', 'XL'],
                'color' => 'Navy',
                'images' => ['watch-polo-life.png', 'watch-polo-navy.png', 'watch-polo-detail.png'],
            ],
            [
                'slug' => 'hoodie-heavyweight-400gsm',
                'sku_prefix' => 'WATCH-HD',
                'name' => 'Hoodie Heavyweight 400GSM',
                'category' => 'ao-ni',
                'description' => "Hoodie 400GSM, nỉ dày, mũ rộng, túi kangaroo.\nGiữ form sau giặt, phù hợp tiết trời se lạnh.\nMàu charcoal dễ phối.",
                'price' => 499000,
                'compare' => 590000,
                'sizes' => ['M', 'L', 'XL'],
                'color' => 'Đen',
                'images' => ['watch-hoodie-life.png', 'watch-hoodie.png', 'watch-hoodie-detail.png'],
            ],
            [
                'slug' => 'so-mi-oxford-dai-tay',
                'sku_prefix' => 'WATCH-OX',
                'name' => 'Áo Sơ Mi Oxford Dài Tay',
                'category' => 'so-mi',
                'description' => "Sơ mi oxford xanh nhạt, cổ điển, dễ ủi.\nMặc sơ vin hoặc thả ngoài. Phù hợp môi trường công sở casual.\nCotton oxford dày vừa, không xuyên thấu.",
                'price' => 399000,
                'compare' => null,
                'sizes' => ['S', 'M', 'L', 'XL'],
                'color' => 'Navy',
                'images' => ['watch-oxford-life.png', 'watch-oxford.png', 'watch-oxford-detail.png'],
            ],
            [
                'slug' => 'ao-khoac-denim-classic',
                'sku_prefix' => 'WATCH-DJ',
                'name' => 'Áo Khoác Denim Classic',
                'category' => 'ao-khoac',
                'description' => "Khoác denim trucker wash vừa, nút kim loại, túi ngực.\nLayer trên áo thun trắng hoặc hoodie.\nBền, càng mặc càng lên màu.",
                'price' => 650000,
                'compare' => 790000,
                'sizes' => ['M', 'L', 'XL'],
                'color' => 'Navy',
                'images' => ['watch-jacket-life.png', 'watch-denim-jacket.png', 'watch-jacket-detail.png'],
            ],
            [
                'slug' => 'quan-jean-slim-fit',
                'sku_prefix' => 'WATCH-JN',
                'name' => 'Quần Jean Slim Fit',
                'category' => 'quan',
                'description' => "Jean slim indigo đậm, co giãn nhẹ, dài phủ giày.\nForm ôm đùi, xuôi ống. Mặc với áo thun, polo hoặc sơ mi.\nKhông xù, không bai gối sớm.",
                'price' => 450000,
                'compare' => null,
                'sizes' => ['S', 'M', 'L', 'XL'],
                'color' => 'Navy',
                'images' => ['watch-jeans-life.png', 'watch-jeans.png', 'watch-jeans-detail.png'],
            ],
            [
                'slug' => 'quan-short-kaki-easy-fit',
                'sku_prefix' => 'WATCH-ST',
                'name' => 'Quần Short Kaki Easy-Fit',
                'category' => 'quan-short',
                'description' => "Short kaki be, cạp vừa, túi sâu, dễ vận động.\nPhù hợp đi chơi, đi làm ngày nóng.\nKaki cotton mềm, không nhăn nhiều.",
                'price' => 350000,
                'compare' => 429000,
                'sizes' => ['S', 'M', 'L'],
                'color' => 'Be',
                'images' => ['watch-shorts-life.png', 'watch-shorts.png', 'watch-shorts-detail.png'],
            ],
            [
                'slug' => 'non-luoi-trai-twill',
                'sku_prefix' => 'WATCH-CAP',
                'name' => 'Nón Lưỡi Trai Twill',
                'category' => 'phu-kien',
                'description' => "Nón lưỡi trai twill đen, đai chỉnh sau.\nForm cổ điển, dễ phối đồ tối giản.\nVải dày, không bai vành.",
                'price' => 199000,
                'compare' => null,
                'sizes' => ['Free size'],
                'color' => 'Đen',
                'images' => ['watch-cap-life.png', 'watch-cap.png', 'watch-cap-detail.png'],
            ],
            [
                'slug' => 'tui-tote-canvas-daily',
                'sku_prefix' => 'WATCH-TOTE',
                'name' => 'Túi Tote Canvas Daily',
                'category' => 'phu-kien',
                'description' => "Tote canvas đáy đứng, quai chắc, đủ laptop 14\".\nĐi học, đi làm, đi chợ đều được.\nCanvas dày, giặt được.",
                'price' => 249000,
                'compare' => 299000,
                'sizes' => ['Free size'],
                'color' => 'Be',
                'images' => ['watch-tote-life.png', 'watch-tote.png', 'watch-tote-detail.png'],
            ],
        ];
    }

    private function brand(): Brand
    {
        $existing = Brand::query()->where('slug', 'demo-brand')->first();
        if ($existing) {
            $existing->update([
                'name' => 'Watch',
                'slug' => 'watch',
                'status' => Brand::STATUS_ACTIVE,
            ]);

            return $existing->refresh();
        }

        return Brand::query()->updateOrCreate(
            ['slug' => 'watch'],
            [
                'code' => (string) Str::ulid(),
                'name' => 'Watch',
                'status' => Brand::STATUS_ACTIVE,
            ],
        );
    }

    /**
     * @return array<string, Category>
     */
    private function categories(): array
    {
        $nam = $this->upsertCategory('nam', 'Nam', 1, null, 'Thời trang nam tối giản, dễ mặc mỗi ngày.');

        $oldApparel = Category::query()->where('slug', 'apparel')->first();
        if ($oldApparel && $oldApparel->id !== $nam->id) {
            Category::query()->where('parent_id', $oldApparel->id)->update(['parent_id' => $nam->id]);
            $oldApparel->update(['status' => Category::STATUS_INACTIVE]);
        }

        $map = ['nam' => $nam];
        foreach ([
            ['ao-thun', 'Áo thun', 1, 'Áo thun nam cotton, oversize và regular.'],
            ['polo', 'Polo', 2, 'Polo dệt kim và pique, mặc đi làm hay cuối tuần.'],
            ['ao-ni', 'Áo nỉ / Hoodie', 3, 'Hoodie và nỉ dày cho tiết trời se lạnh.'],
            ['so-mi', 'Sơ mi', 4, 'Sơ mi oxford và casual.'],
            ['ao-khoac', 'Áo khoác', 5, 'Khoác denim và outerwear nhẹ.'],
            ['quan', 'Quần jean', 6, 'Jean nam form slim và regular.'],
            ['quan-short', 'Quần short', 7, 'Short kaki và cotton.'],
            ['phu-kien', 'Phụ kiện', 8, 'Nón, túi tote và phụ kiện hằng ngày.'],
        ] as [$slug, $name, $pos, $desc]) {
            $map[$slug] = $this->upsertCategory($slug, $name, $pos, $nam->id, $desc);
        }

        $oldTee = Category::query()->where('slug', 't-shirts')->first();
        if ($oldTee && ($map['ao-thun']->id ?? null) !== $oldTee->id) {
            $oldTee->update(['status' => Category::STATUS_INACTIVE]);
        }

        return $map;
    }

    private function upsertCategory(
        string $slug,
        string $name,
        int $position,
        ?int $parentId,
        string $description,
    ): Category {
        $category = Category::query()->firstOrCreate(
            ['slug' => $slug],
            [
                'code' => (string) Str::ulid(),
                'parent_id' => $parentId,
                'name' => $name,
                'position' => $position,
                'status' => Category::STATUS_ACTIVE,
                'description' => $description,
                'meta_title' => $name.' — Watch',
                'meta_description' => $description,
            ],
        );
        $category->fill([
            'parent_id' => $parentId,
            'name' => $name,
            'position' => $position,
            'status' => Category::STATUS_ACTIVE,
            'description' => $description,
            'meta_title' => $name.' — Watch',
            'meta_description' => $description,
        ])->save();

        return $category;
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  array<string, Category>  $categories
     * @param  array<string, AttributeOption>  $sizes
     * @param  array<string, AttributeOption>  $colors
     */
    private function seedProduct(
        array $item,
        Brand $brand,
        array $categories,
        Attribute $sizeAttr,
        Attribute $colorAttr,
        array $sizes,
        array $colors,
        Warehouse $warehouse,
        int $index,
    ): void {
        $publishedAt = now()->subHours($index);
        $product = Product::query()->firstOrCreate(
            ['slug' => $item['slug']],
            [
                'code' => (string) Str::ulid(),
                'brand_id' => $brand->id,
                'name' => $item['name'],
                'status' => Product::STATUS_ACTIVE,
                'published_at' => $publishedAt,
                'description' => $item['description'],
                'meta_title' => $item['name'].' — Watch',
                'meta_description' => Str::limit(str_replace("\n", ' ', $item['description']), 150),
            ],
        );
        $product->fill([
            'brand_id' => $brand->id,
            'name' => $item['name'],
            'status' => Product::STATUS_ACTIVE,
            'published_at' => $publishedAt,
            'description' => $item['description'],
            'meta_title' => $item['name'].' — Watch',
            'meta_description' => Str::limit(str_replace("\n", ' ', $item['description']), 150),
        ])->save();

        $category = $categories[$item['category']] ?? $categories['ao-thun'];
        $syncIds = [$category->id];
        if (isset($categories['nam']) && $categories['nam']->id !== $category->id) {
            $syncIds[] = $categories['nam']->id;
        }
        $product->categories()->sync($syncIds);

        $paths = [];
        foreach ($item['images'] as $filename) {
            $paths[] = $this->importFixture((string) $filename);
        }

        $product->images()->withTrashed()->forceDelete();
        foreach ($paths as $index => $path) {
            ProductImage::query()->create([
                'product_id' => $product->id,
                'path' => $path,
                'alt' => $item['name'],
                'position' => $index,
                'is_primary' => $index === 0,
            ]);
        }

        $keptIds = [];
        $colorOpt = $colors[$item['color']] ?? null;
        $sizeLabels = $item['sizes'];
        foreach ($sizeLabels as $i => $sizeLabel) {
            $sku = $item['sku_prefix'].'-'.Str::upper(Str::slug($sizeLabel));
            if ($item['sku_prefix'] === 'DEMO-TEE' && in_array($sizeLabel, ['S', 'M'], true)) {
                $sku = 'DEMO-TEE-'.$sizeLabel;
            }

            $variant = ProductVariant::query()->firstOrCreate(
                ['sku' => $sku],
                [
                    'code' => (string) Str::ulid(),
                    'product_id' => $product->id,
                    'price' => $item['price'],
                    'compare_at_price' => $item['compare'],
                    'is_default' => $i === 0,
                    'status' => ProductVariant::STATUS_ACTIVE,
                ],
            );
            $variant->fill([
                'product_id' => $product->id,
                'price' => $item['price'],
                'compare_at_price' => $item['compare'],
                'is_default' => $i === 0,
                'status' => ProductVariant::STATUS_ACTIVE,
            ])->save();
            $keptIds[] = $variant->id;

            $sync = [];
            if (isset($sizes[$sizeLabel])) {
                $sync[$sizes[$sizeLabel]->id] = ['attribute_id' => $sizeAttr->id];
            } else {
                $free = $this->option($sizeAttr, $sizeLabel, 20);
                $sync[$free->id] = ['attribute_id' => $sizeAttr->id];
            }
            if ($colorOpt) {
                $sync[$colorOpt->id] = ['attribute_id' => $colorAttr->id];
            }
            $variant->attributeOptions()->sync($sync);

            StockItem::query()->updateOrCreate(
                [
                    'warehouse_id' => $warehouse->id,
                    'product_variant_id' => $variant->id,
                ],
                [
                    'qty_on_hand' => 40,
                    'qty_reserved' => 0,
                ],
            );
        }

        $product->variants()->whereNotIn('id', $keptIds)->each(function (ProductVariant $variant): void {
            $variant->attributeOptions()->detach();
            StockItem::query()->where('product_variant_id', $variant->id)->delete();
            $variant->delete();
        });
    }

    private function attribute(string $slug, string $name, int $position): Attribute
    {
        $attribute = Attribute::query()->firstOrCreate(
            ['slug' => $slug],
            [
                'code' => (string) Str::ulid(),
                'name' => $name,
                'position' => $position,
            ],
        );
        $attribute->fill([
            'name' => $name,
            'position' => $position,
        ])->save();

        return $attribute;
    }

    private function option(Attribute $attribute, string $label, int $position): AttributeOption
    {
        return AttributeOption::query()->firstOrCreate(
            ['attribute_id' => $attribute->id, 'label' => $label],
            [
                'code' => (string) Str::ulid(),
                'position' => $position,
            ],
        );
    }

    private function importFixture(string $filename): string
    {
        $key = pathinfo($filename, PATHINFO_FILENAME);
        $path = 'catalog/'.$key.'.jpg';
        $thumb = 'catalog/'.$key.'_thumb.jpg';
        $disk = Storage::disk('public');
        if ($disk->exists($path) && $disk->exists($thumb)) {
            return $path;
        }

        $source = database_path('seeders/fixtures/catalog/'.$filename);
        if (! is_file($source)) {
            $disk->put($path, $this->placeholderJpeg(900, 1200, $key));
            $disk->put($thumb, $this->placeholderJpeg(400, 400, $key));

            return $path;
        }

        $manager = new ImageManager(new Driver);
        $image = $manager->decodePath($source);
        $image->scaleDown(1200, 1600);
        $disk->put($path, (string) $image->encodeUsingFileExtension('jpg'));

        $thumbImage = $manager->decodePath($source);
        $thumbImage->scaleDown(400, 400);
        $disk->put($thumb, (string) $thumbImage->encodeUsingFileExtension('jpg'));

        return $path;
    }

    private function placeholderJpeg(int $width, int $height, string $label): string
    {
        if (! function_exists('imagecreatetruecolor')) {
            return (string) base64_decode('R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==', true);
        }

        $canvas = imagecreatetruecolor($width, $height);
        $bg = imagecolorallocate($canvas, 248, 250, 252);
        $fg = imagecolorallocate($canvas, 15, 23, 42);
        imagefill($canvas, 0, 0, $bg);
        imagestring($canvas, 5, (int) ($width * 0.2), (int) ($height * 0.48), $label, $fg);
        ob_start();
        imagejpeg($canvas, null, 82);
        $bytes = (string) ob_get_clean();
        imagedestroy($canvas);

        return $bytes;
    }
}
