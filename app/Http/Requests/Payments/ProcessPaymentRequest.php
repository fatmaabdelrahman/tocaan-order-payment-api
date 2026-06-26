<?php

namespace App\Http\Requests\Payments;

use App\Services\Payments\PaymentGatewayFactory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcessPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // Only methods with a configured gateway are accepted, so adding a
        // gateway in config/payments.php automatically makes it valid here.
        $supported = app(PaymentGatewayFactory::class)->supportedMethods();

        return [
            'method' => ['required', 'string', Rule::in($supported)],
        ];
    }

    public function messages(): array
    {
        return [
            'method.in' => 'The selected payment method is not supported.',
        ];
    }
}
