<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'trip_id' => ['required', 'integer', 'min:1'],
            'start_station_id' => ['required', 'integer', 'min:1'],
            'end_station_id' => ['required', 'integer', 'min:1'],
            'seat_number' => ['required', 'integer', 'min:1', 'max:12'],
            'passenger' => ['required', 'array'],
            'passenger.name' => ['required', 'string', 'max:255'],
            'passenger.email' => ['required', 'email'],
        ];
    }
}
