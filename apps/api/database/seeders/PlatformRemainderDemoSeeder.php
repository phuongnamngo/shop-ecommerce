<?php

namespace Database\Seeders;

use App\Models\CmsBanner;
use App\Models\CmsPage;
use App\Models\GeoDistrict;
use App\Models\GeoProvince;
use App\Models\GeoWard;
use App\Models\ProductImage;
use App\Models\Setting;
use App\Support\CatalogImagePath;
use Illuminate\Database\Seeder;

class PlatformRemainderDemoSeeder extends Seeder
{
    public function run(): void
    {
        // GHN sandbox Hà Nội / Ba Đình / Phúc Xá (ProvinceID / DistrictID / WardCode).
        // https://api.ghn.vn/home/docs/detail?id=77 (GetProvince / GetDistrict / GetWard)
        $province = GeoProvince::query()->firstOrCreate(
            ['code' => '201'],
            ['name' => 'Hà Nội'],
        );

        $district = GeoDistrict::query()->firstOrCreate(
            ['geo_province_id' => $province->id, 'code' => '1484'],
            ['name' => 'Ba Đình'],
        );

        GeoWard::query()->firstOrCreate(
            ['geo_district_id' => $district->id, 'code' => '1A0106'],
            ['name' => 'Phúc Xá'],
        );

        Setting::query()->firstOrCreate(
            ['key' => 'site.name'],
            ['value' => ['vi' => 'Watch Shop'], 'group' => 'site'],
        );

        Setting::query()->firstOrCreate(
            ['key' => 'currency.default'],
            ['value' => ['code' => 'VND'], 'group' => 'currency'],
        );

        $pages = [
            'about' => [
                'title' => 'Về chúng tôi',
                'body' => "## Watch\n\nWatch là cửa hàng thời trang nam tối giản. Giá và tồn kho luôn lấy từ hệ thống.",
            ],
            'shipping' => [
                'title' => 'Vận chuyển',
                'body' => "## Giao hàng\n\nGiao hàng toàn quốc. Phí vận chuyển được tính tại bước thanh toán.",
            ],
            'returns' => [
                'title' => 'Đổi trả',
                'body' => "## Đổi trả 30 ngày\n\nĐổi size hoặc hoàn nếu sản phẩm còn nguyên tem trong 30 ngày.",
            ],
            'privacy' => [
                'title' => 'Chính sách bảo mật',
                'body' => "## Bảo mật\n\nChúng tôi chỉ dùng thông tin đơn hàng để xử lý giao hàng và hỗ trợ khách.",
            ],
        ];

        foreach ($pages as $slug => $attrs) {
            CmsPage::query()->firstOrCreate(
                ['slug' => $slug],
                [
                    'title' => $attrs['title'],
                    'body' => $attrs['body'],
                    'status' => CmsPage::STATUS_PUBLISHED,
                    'published_at' => now(),
                ],
            );
        }

        CmsBanner::query()->firstOrCreate(
            ['placement' => CmsBanner::PLACEMENT_PROMO_BAR],
            [
                'title' => 'Miễn phí vận chuyển cho đơn từ 499.000₫ · Đổi trả trong 30 ngày',
                'image_url' => null,
                'link_url' => '/products',
                'sort' => 0,
                'status' => CmsBanner::STATUS_ACTIVE,
            ],
        );

        $heroPath = ProductImage::query()->value('path');
        $heroUrl = is_string($heroPath) && $heroPath !== ''
            ? CatalogImagePath::url($heroPath)
            : '/storage/cms/hero-placeholder.jpg';

        CmsBanner::query()->firstOrCreate(
            ['placement' => CmsBanner::PLACEMENT_HOMEPAGE_HERO],
            [
                'title' => 'NEW SEASON / URBAN ESSENTIALS',
                'image_url' => $heroUrl,
                'link_url' => '/products',
                'sort' => 0,
                'status' => CmsBanner::STATUS_ACTIVE,
            ],
        );
    }
}
