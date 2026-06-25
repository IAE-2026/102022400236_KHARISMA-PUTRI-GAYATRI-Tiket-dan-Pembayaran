<?php

namespace App\Http\Requests;

use App\Support\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cust_name'   => ['required', 'string', 'max:255'],
            'route_id'    => ['required', 'integer', 'min:1'],
            'seat_number' => ['required', 'string', 'max:20'],
            'price'       => ['required', 'numeric', 'min:0'],
            'payment_method' => ['required', 'in:QRIS,credit_card,bank_transfer'],
        ];
    }

    public function messages(): array
    {
        return [
            'cust_name.required'   => 'Customer name is required',
            'route_id.required'    => 'Route ID is required',
            'seat_number.required' => 'Seat number is required',
            'price.required'       => 'Price is required',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ApiResponse::error('Validation failed', $validator->errors(), 422)
        );
    }
}
