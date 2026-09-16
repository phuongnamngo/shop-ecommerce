<?php

namespace App\Http\Requests\Api\V1\Admin\Settings;

use App\Support\SettingsAllowlist;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $value = $this->input('value');
        if (! is_array($value)) {
            return;
        }

        if (is_string($value['vi'] ?? null)) {
            $value['vi'] = trim($value['vi']);
        }

        if (is_string($value['code'] ?? null)) {
            $value['code'] = strtoupper(trim($value['code']));
        }

        $this->merge(['value' => $value]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $key = (string) $this->route('key');

        if ($key === SettingsAllowlist::SITE_NAME) {
            return [
                'value' => ['required', 'array:vi'],
                'value.vi' => ['required', 'string', 'max:80'],
            ];
        }

        if ($key === SettingsAllowlist::CURRENCY_DEFAULT) {
            return [
                'value' => ['required', 'array:code'],
                'value.code' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            ];
        }

        return [];
    }
}
