<?php

namespace App\Providers;

use App\Infrastructure\Locking\RedisSeatLock;
use App\Infrastructure\Persistence\LaravelTransactionBoundary;
use App\Infrastructure\Persistence\QueryBookingRepository;
use App\Infrastructure\Persistence\QueryBusRepository;
use App\Infrastructure\Persistence\QueryStationRepository;
use App\Infrastructure\Persistence\QueryTripRepository;
use Fleet\Application\TransactionBoundary;
use Fleet\Domain\Booking\Port\BookingRepository;
use Fleet\Domain\Booking\Port\SeatLock;
use Fleet\Domain\Bus\Port\BusRepository;
use Fleet\Domain\Station\Port\StationRepository;
use Fleet\Domain\Trip\Port\TripRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(StationRepository::class, QueryStationRepository::class);
        $this->app->bind(BusRepository::class, QueryBusRepository::class);
        $this->app->bind(TripRepository::class, QueryTripRepository::class);
        $this->app->bind(BookingRepository::class, QueryBookingRepository::class);
        $this->app->bind(TransactionBoundary::class, LaravelTransactionBoundary::class);
        $this->app->bind(SeatLock::class, RedisSeatLock::class);
    }

    public function boot(): void
    {
        //
    }
}
