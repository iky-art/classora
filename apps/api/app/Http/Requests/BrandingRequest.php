<?php

namespace App\Http\Requests;

class BrandingRequest extends TenantRequest
{
    public function rules(): array
    {
        return [
            'app_name' => ['nullable', 'string', 'max:80'],
            'primary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'login_title' => ['nullable', 'string', 'max:120'],
            'login_message' => ['nullable', 'string', 'max:500'],
            'report_header' => ['nullable', 'string', 'max:500'],
            'public_page_title' => ['nullable', 'string', 'max:120'],
        ];
    }
}
