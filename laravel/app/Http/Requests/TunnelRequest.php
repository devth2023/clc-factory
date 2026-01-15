<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Tunnel request validation.
 *
 * Validates /sync endpoint requests for coordinate and payload.
 *
 * @property string $target The coordinate key to resolve
 * @property mixed $payload Optional payload data
 */
final class TunnelRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * All requests are initially authorized; permission checks happen in middleware.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'target' => [
                'required',
                'string',
                'max:64',
                'regex:/^[A-Z0-9_]+$/',
            ],
            'payload' => [
                'nullable',
                'json',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'target.required' => 'Coordinate target is required.',
            'target.regex' => 'Coordinate target must contain only uppercase letters, numbers, and underscores.',
            'target.max' => 'Coordinate target must not exceed 64 characters.',
            'payload.json' => 'Payload must be valid JSON.',
        ];
    }

    /**
     * Get the target coordinate key.
     *
     * @return string
     */
    public function getTarget(): string
    {
        return $this->string('target')->value();
    }

    /**
     * Get the payload as array.
     *
     * @return array<string, mixed>
     */
    public function getPayload(): array
    {
        if ($this->has('payload') && is_string($this->input('payload'))) {
            return json_decode($this->input('payload'), true, 512, JSON_THROW_ON_ERROR) ?? [];
        }

        return $this->input('payload') ?? [];
    }
}
