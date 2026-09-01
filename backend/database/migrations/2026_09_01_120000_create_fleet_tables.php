<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('buses', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('bus_id')->constrained('buses')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('trip_stations', function (Blueprint $table) {
            $table->foreignId('trip_id')->constrained('trips')->cascadeOnDelete();
            $table->foreignId('station_id')->constrained('stations')->restrictOnDelete();
            $table->unsignedInteger('position');

            $table->primary(['trip_id', 'station_id']);
            $table->unique(['trip_id', 'position']);
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained('trips')->restrictOnDelete();
            $table->unsignedTinyInteger('seat_number');
            $table->foreignId('start_station_id')->constrained('stations')->restrictOnDelete();
            $table->foreignId('end_station_id')->constrained('stations')->restrictOnDelete();
            $table->unsignedInteger('start_position');
            $table->unsignedInteger('end_position');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('passenger_name');
            $table->string('passenger_email');
            $table->timestamps();

            $table->index(['trip_id', 'seat_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('trip_stations');
        Schema::dropIfExists('trips');
        Schema::dropIfExists('buses');
        Schema::dropIfExists('stations');
    }
};
