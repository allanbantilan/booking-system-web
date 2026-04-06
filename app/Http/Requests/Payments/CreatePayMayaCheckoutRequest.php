<?php

namespace App\Http\Requests\Payments;

use Illuminate\Foundation\Http\FormRequest;

class CreatePayMayaCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'booking_id' => ['required', 'integer', 'exists:bookings,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'nights' => ['nullable', 'integer', 'min:1'],
            'check_in_date' => ['nullable', 'date'],
            'check_out_date' => ['nullable', 'date'],
        ];
    }
}
