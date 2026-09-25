<?php

declare(strict_types=1);

namespace Tests\Concerns;

use Illuminate\Support\Facades\Cache;

trait RequiresRedisCacheStore
{
    private function skipUnlessRedisStoreWorks(): void
    {
        $hosts = array_values(array_unique(array_filter([
            (string) $this->app['config']->get('database.redis.default.host'),
            '127.0.0.1',
        ])));

        foreach ($hosts as $host) {
            try {
                $this->app['config']->set('database.redis.default.host', $host);
                $this->app['config']->set('database.redis.cache.host', $host);
                $this->app->make('redis')->purge();
                $this->app->forgetInstance('redis');
                Cache::forgetDriver('redis');

                Cache::store('redis')->put('fleet:lock-probe', '1', 5);
                Cache::store('redis')->forget('fleet:lock-probe');

                return;
            } catch (\Throwable) {
                continue;
            }
        }

        $this->markTestSkipped('Redis is required for the cache lock test.');
    }
}
