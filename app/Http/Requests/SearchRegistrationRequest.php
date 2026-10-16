<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Rapikan input sebelum divalidasi. */
    protected function prepareForValidation(): void
    {
        $this->merge(['q' => trim((string) $this->input('q'))]);
    }

    public function rules(): array
    {
        return [
            'q' => ['bail', 'required', 'string', 'max:150', function (string $attribute, mixed $value, \Closure $fail) {
                if ($this->identifier() === null) {
                    $fail('Masukkan email, nomor HP (contoh: 081234567890), atau nomor registrasi (contoh: RKT-AB12CD) yang valid.');
                }
            }],
        ];
    }

    public function messages(): array
    {
        return [
            'q.required' => 'Isi email, nomor HP, atau nomor registrasi.',
            'q.max' => 'Input terlalu panjang.',
        ];
    }

    /**
     * Kenali jenis input: email, nomor registrasi, atau nomor HP.
     * Mengembalikan null bila formatnya tidak valid untuk jenis yang terdeteksi.
     *
     * @return array{type: 'email'|'code'|'phone', value: string}|null
     */
    public function identifier(): ?array
    {
        $q = trim((string) $this->input('q'));

        // Email: mengandung "@"
        if (str_contains($q, '@')) {
            return filter_var($q, FILTER_VALIDATE_EMAIL)
                ? ['type' => 'email', 'value' => mb_strtolower($q)]
                : null;
        }

        // Nomor registrasi: diawali RKT (tanda "-" boleh dilewatkan)
        $code = strtoupper(preg_replace('/\s+/', '', $q));
        if (str_starts_with($code, 'RKT')) {
            return preg_match('/^RKT-?[A-Z0-9]{6}$/', $code)
                ? ['type' => 'code', 'value' => 'RKT-'.substr($code, -6)]
                : null;
        }

        // Nomor HP: selain itu
        $phone = preg_replace('/[\s\-().]/', '', $q);

        return preg_match('/^(\+62|62|0)8[0-9]{7,13}$/', $phone)
            ? ['type' => 'phone', 'value' => $phone]
            : null;
    }
}
