<?php

namespace Database\Factories;

use App\Models\CmsBanner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CmsBanner>
 */
class CmsBannerFactory extends Factory
{
    protected $model = CmsBanner::class;

    public function definition(): array
    {
        return [
            'placement' => CmsBanner::PLACEMENT_PROMO_BAR,
            'title' => fake()->sentence(4),
            'image_url' => null,
            'link_url' => '/products',
            'starts_at' => null,
            'ends_at' => null,
            'sort' => 0,
            'status' => CmsBanner::STATUS_ACTIVE,
        ];
    }
}
