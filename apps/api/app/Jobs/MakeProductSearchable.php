<?php

namespace App\Jobs;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class MakeProductSearchable implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $productId) {}

    public function handle(): void
    {
        $product = Product::query()->find($this->productId);
        if ($product === null || ! $product->shouldBeSearchable()) {
            return;
        }

        $product->searchable();
    }
}
