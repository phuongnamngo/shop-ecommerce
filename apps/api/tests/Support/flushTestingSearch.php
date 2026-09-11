<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Meilisearch\Client;
use Meilisearch\Contracts\TasksQuery;

function flushTestingSearchIndexes(): void
{
    $client = app(Client::class);

    foreach ([Product::class, Brand::class, Category::class] as $class) {
        $uid = (new $class)->searchableAs();
        try {
            waitForMeiliTask($client, $client->deleteIndex($uid));
        } catch (Throwable) {
            // Index may not exist yet.
        }
    }
}

function waitForTestingSearchIdle(): void
{
    try {
        usleep(100_000);

        $client = app(Client::class);
        $prefix = (string) config('scout.prefix');
        $indexUids = [
            $prefix.'products',
            $prefix.'brands',
            $prefix.'categories',
        ];
        $deadline = microtime(true) + 5;

        do {
            $query = (new TasksQuery)
                ->setStatuses(['enqueued', 'processing'])
                ->setIndexUids($indexUids)
                ->setLimit(100);
            $results = $client->getTasks($query)->getResults();
            if ($results === []) {
                usleep(50_000);

                return;
            }
            foreach ($results as $task) {
                $uid = meiliTaskUid($task);
                if ($uid > 0) {
                    $client->waitForTask($uid);
                }
            }
        } while (microtime(true) < $deadline);
    } catch (Throwable) {
        // Search/unavailable tests may rebind the Meili host.
    }
}

function syncPublicCatalogSearch(): void
{
    Product::query()->get()->each(function (Product $product): void {
        if ($product->shouldBeSearchable()) {
            $product->searchableSync();
        } else {
            $product->unsearchableSync();
        }
    });
    waitForTestingSearchIdle();
}

function waitForMeiliTask(Client $client, mixed $task): void
{
    $uid = meiliTaskUid($task);
    if ($uid > 0) {
        $client->waitForTask($uid);
    }
}

function meiliTaskUid(mixed $task): int
{
    if (is_array($task)) {
        return (int) ($task['taskUid'] ?? $task['uid'] ?? 0);
    }

    if (is_object($task) && method_exists($task, 'getUid')) {
        return (int) $task->getUid();
    }

    return 0;
}
