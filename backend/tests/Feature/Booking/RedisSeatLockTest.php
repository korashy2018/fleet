<?php

declare(strict_types=1);

namespace Tests\Feature\Booking;

use App\Infrastructure\Locking\RedisSeatLock;
use Fleet\Application\Booking\LockUnavailable;
use Fleet\Domain\Booking\SeatLock;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class RedisSeatLockTest extends TestCase
{
    #[Test]
    public function it_fails_closed_when_redis_cannot_be_reached(): void
    {
        Cache::shouldReceive('store')->once()->with('redis')->andThrow(new \RuntimeException('connection refused'));

        $this->expectException(LockUnavailable::class);

        $this->app->make(RedisSeatLock::class)->acquire(1, 5, static fn (): bool => true);
    }

    #[Test]
    public function the_container_binds_the_redis_lock(): void
    {
        $this->assertInstanceOf(RedisSeatLock::class, $this->app->make(SeatLock::class));
    }

    #[Test]
    public function cache_lock_is_exclusive_on_the_same_seat_key(): void
    {
        $this->skipUnlessRedisStoreWorks();

        $key = 'booking:1:9';
        $held = Cache::store('redis')->lock($key, 15);
        $this->assertTrue($held->get());

        try {
            $this->assertFalse(Cache::store('redis')->lock($key, 15)->get());
        } finally {
            $held->release();
        }

        $after = Cache::store('redis')->lock($key, 15);
        $this->assertTrue($after->get());
        $after->release();
    }

    #[Test]
    public function acquire_runs_the_action_while_the_cache_lock_is_held(): void
    {
        $this->skipUnlessRedisStoreWorks();

        $ran = false;

        $result = $this->app->make(RedisSeatLock::class)->acquire(1, 9, static function () use (&$ran): string {
            $ran = true;

            return 'booked';
        });

        $this->assertTrue($ran);
        $this->assertSame('booked', $result);
        $this->assertTrue(Cache::store('redis')->lock('booking:1:9', 15)->get());
    }

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
