<?php

namespace App\Providers;

use App\Infrastructure\Persistence\QueryBookingRepository;
use App\Infrastructure\Persistence\QueryBusRepository;
use App\Infrastructure\Persistence\QueryStationRepository;
use App\Infrastructure\Persistence\QueryTripRepository;
use Fleet\Domain\Booking\BookingRepository;
use Fleet\Domain\Bus\BusRepository;
use Fleet\Domain\Station\StationRepository;
use Fleet\Domain\Trip\TripRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(StationRepository::class, QueryStationRepository::class);
        $this->app->bind(BusRepository::class, QueryBusRepository::class);
        $this->app->bind(TripRepository::class, QueryTripRepository::class);
        $this->app->bind(BookingRepository::class, QueryBookingRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
