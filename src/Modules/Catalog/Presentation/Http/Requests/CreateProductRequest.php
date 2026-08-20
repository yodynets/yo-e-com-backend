<?php

declare(strict_types=1);

namespace Yeod\Modules\Catalog\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Yeod\Modules\Catalog\Application\Command\CreateProduct\CreateProductCommand;
use Yeod\Shared\Domain\ValueObject\Currency;

/**
 * Validates the payload of `POST /api/catalog/products`.
 *
 * Validation here is about shape and user feedback only. Business invariants stay
 * in the Domain: the aggregate never trusts the transport layer.
 */
final class CreateProductRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'sku' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9][A-Za-z0-9._\-]*$/'],
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', Rule::in(array_column(Currency::cases(), 'value'))],
        ];
    }

    /**
     * Translate the validated payload into an Application command.
     */
    public function toCommand(): CreateProductCommand
    {
        /** @var array{sku: string, name: string, price: string|float|int, currency: string} $payload */
        $payload = $this->validated();

        return CreateProductCommand::fromArray($payload);
    }
}
