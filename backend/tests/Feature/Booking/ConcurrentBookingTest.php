<?php

declare(strict_types=1);

namespace Tests\Feature\Booking;

use App\Infrastructure\Locking\RedisSeatLock;
use App\Infrastructure\Persistence\QueryBookingRepository;
use Fleet\Application\Booking\BookSeat;
use Fleet\Application\Booking\LockUnavailable;
use Fleet\Domain\Booking\Exception\SeatUnavailable;
use Fleet\Domain\Booking\Port\BookingRepository;
use Fleet\Domain\Booking\Port\SeatLock;
use Fleet\Domain\Booking\ValueObject\Passenger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\RequiresRedisCacheStore;
use Tests\Doubles\ExclusiveSeatLock;
use Tests\Doubles\NotifyOnEmptySeatRead;
use Tests\Doubles\PassThroughSeatLock;
use Tests\Fixtures\EgyptCatalog;
use Tests\TestCase;

final class ConcurrentBookingTest extends TestCase
{
    use RefreshDatabase;
    use RequiresRedisCacheStore;

    private const SEAT = 9;

    protected function setUp(): void
    {
        parent::setUp();

        EgyptCatalog::seed();
    }

    #[Test]
    public function the_second_http_guest_gets_409_and_only_one_row_is_stored(): void
    {
        $this->app->instance(SeatLock::class, new PassThroughSeatLock());

        $this->postJson('/api/v1/bookings', $this->cairoToMinya('omar@example.com'))
            ->assertCreated();

        $this->postJson('/api/v1/bookings', $this->cairoToMinya('mona@example.com'))
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'seat_unavailable');

        $this->assertSame(1, $this->bookingsOnRaceSeat());
    }

    #[Test]
    public function without_a_mutex_two_writers_can_insert_overlapping_rows(): void
    {
        $this->app->instance(SeatLock::class, new PassThroughSeatLock());
        $this->contendDuringEmptyRead();

        $this->book('omar@example.com');

        $this->assertSame(2, $this->bookingsOnRaceSeat());
    }

    #[Test]
    public function a_held_lock_keeps_the_contender_from_inserting(): void
    {
        $this->app->instance(SeatLock::class, new ExclusiveSeatLock());
        $this->contendDuringEmptyRead();

        $this->book('omar@example.com');

        $this->assertSame(1, $this->bookingsOnRaceSeat());
    }

    #[Test]
    public function the_loser_rereads_under_the_redis_lock_and_gets_seat_unavailable(): void
    {
        $this->skipUnlessRedisStoreWorks();

        $this->book('omar@example.com');

        try {
            $this->book('mona@example.com');
            $this->fail('The second writer should lose after the first commits.');
        } catch (SeatUnavailable) {
        }

        $this->assertSame(1, $this->bookingsOnRaceSeat());
    }

    #[Test]
    public function a_writer_cannot_enter_while_the_redis_lock_is_held(): void
    {
        $this->skipUnlessRedisStoreWorks();

        $this->app->bind(SeatLock::class, static fn (): RedisSeatLock => new RedisSeatLock(waitSeconds: 0));

        $held = Cache::store('redis')->lock(
            sprintf('booking:%d:%d', EgyptCatalog::TRIP_CAIRO_ASYUT, self::SEAT),
            15,
        );
        $this->assertTrue($held->get());

        try {
            $this->book('omar@example.com');
            $this->fail('BookSeat should not run while the Redis key is held.');
        } catch (LockUnavailable) {
        } finally {
            $held->release();
        }

        $this->assertSame(0, $this->bookingsOnRaceSeat());
    }

    private function contendDuringEmptyRead(): void
    {
        $this->app->instance(
            BookingRepository::class,
            new NotifyOnEmptySeatRead(
                $this->app->make(QueryBookingRepository::class),
                function (): void {
                    try {
                        $this->book('mona@example.com');
                    } catch (LockUnavailable) {
                    }
                },
            ),
        );
    }

    private function book(string $email): void
    {
        $this->app->make(BookSeat::class)->handle(
            EgyptCatalog::TRIP_CAIRO_ASYUT,
            EgyptCatalog::CAIRO,
            EgyptCatalog::MINYA,
            self::SEAT,
            new Passenger('Guest', $email),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function cairoToMinya(string $email): array
    {
        return [
            'trip_id' => EgyptCatalog::TRIP_CAIRO_ASYUT,
            'start_station_id' => EgyptCatalog::CAIRO,
            'end_station_id' => EgyptCatalog::MINYA,
            'seat_number' => self::SEAT,
            'passenger' => [
                'name' => 'Guest',
                'email' => $email,
            ],
        ];
    }

    private function bookingsOnRaceSeat(): int
    {
        return DB::table('bookings')
            ->where('trip_id', EgyptCatalog::TRIP_CAIRO_ASYUT)
            ->where('seat_number', self::SEAT)
            ->count();
    }
}
