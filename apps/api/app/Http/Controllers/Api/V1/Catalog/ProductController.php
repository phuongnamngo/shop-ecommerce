<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\Catalog\ProductResource;
use App\Models\Product;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $products = Product::query()
            ->where('status', Product::STATUS_ACTIVE)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->with('defaultVariant')
            ->latest('id')
            ->paginate(20);

        return ProductResource::collection($products);
    }
}
