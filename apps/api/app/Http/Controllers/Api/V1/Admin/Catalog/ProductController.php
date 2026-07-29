<?php

namespace App\Http\Controllers\Api\V1\Admin\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\Catalog\ProductResource;
use App\Models\Product;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $products = Product::query()
            ->with('defaultVariant')
            ->latest('id')
            ->paginate(20);

        return ProductResource::collection($products);
    }
}
