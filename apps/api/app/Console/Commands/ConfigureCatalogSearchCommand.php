<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Console\Command;
use Meilisearch\Client;
use Throwable;

final class ConfigureCatalogSearchCommand extends Command
{
    protected $signature = 'catalog:search-configure';

    protected $description = 'Apply Meilisearch filterable, sortable, and searchable settings for catalog indexes';

    public function handle(Client $client): int
    {
        foreach ([
            (new Product)->searchableAs(),
            (new Brand)->searchableAs(),
            (new Category)->searchableAs(),
        ] as $uid) {
            try {
                $this->wait($client, $client->createIndex($uid, ['primaryKey' => 'id']));
            } catch (Throwable) {
                // Index already exists.
            }
        }

        $product = $client->index((new Product)->searchableAs());
        $this->wait($client, $product->updateSearchableAttributes([
            'name',
            'description',
            'brand_name',
            'category_names',
            'skus',
            'attribute_labels',
        ]));
        $this->wait($client, $product->updateFilterableAttributes([
            'brand_id',
            'category_ids',
            'price_bucket',
            'attribute_facets',
        ]));
        $this->wait($client, $product->updateSortableAttributes([
            'price',
            'published_at',
            'id',
        ]));

        $brand = $client->index((new Brand)->searchableAs());
        $this->wait($client, $brand->updateSearchableAttributes(['name', 'slug']));

        $category = $client->index((new Category)->searchableAs());
        $this->wait($client, $category->updateSearchableAttributes(['name', 'slug']));

        $this->info('Catalog search indexes configured.');

        return self::SUCCESS;
    }

    private function wait(Client $client, mixed $task): void
    {
        $uid = is_array($task)
            ? (int) ($task['taskUid'] ?? $task['uid'])
            : (int) $task->getUid();

        $client->waitForTask($uid);
    }
}
