<?php

namespace App\Http\Requests\FinancialBlock;

use App\Helpers\DateFormatter;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserFinancialBlockRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
    protected function prepareForValidation()
    {
        if ($this->restricted_until) {
            $this->merge([
                'restricted_until' => DateFormatter::convertPersianToCarbonDate($this->restricted_until),
            ]);
        }
    }
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'restricted_until' => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'restricted_until.date' => 'The restricted until date must be a valid date.',
            'restricted_until.after_or_equal' => 'زمان پایان محدودیت باید برای امروز یا آینده باشد.',
        ];
    }
}
