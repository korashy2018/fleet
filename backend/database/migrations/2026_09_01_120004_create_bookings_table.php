<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
    }
};
