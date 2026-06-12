<?php

namespace App\Http\Requests;

use App\Support\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cust_name' => ['required', 'string', 'max:255'],
            'route_id' => ['required', 'integer', 'min:1'],
            'seat_number' => [
                'required',
                'string',
                'max:20',
                Rule::unique('tickets', 'seat_number')
                    ->where(fn ($query) => $query
                        ->where('route_id', $this->input('route_id'))
                        ->where('status', 'booked')),
            ],
            'price' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'seat_number.unique' => 'Seat is already booked for this route',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ApiResponse::error('Validation failed', $validator->errors(), 422)
        );
    }
}
