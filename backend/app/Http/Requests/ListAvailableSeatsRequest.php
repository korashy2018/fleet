<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListAvailableSeatsRequest extends FormRequest
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
            'start_station_id' => ['required', 'integer', 'min:1'],
            'end_station_id' => ['required', 'integer', 'min:1'],
        ];
    }
}
