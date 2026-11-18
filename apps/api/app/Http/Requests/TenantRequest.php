<?php

namespace App\Http\Requests;

use App\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;

/** Aturan exists/unique selalu dibatasi pada sekolah aktif, sehingga relasi lintas sekolah ditolak. */
abstract class TenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // otorisasi dilakukan oleh Policy di controller
    }

    protected function inSchool(string $table): Exists
    {
        return Rule::exists($table, 'id')->where('school_id', app(TenantContext::class)->schoolId());
    }

    protected function uniqueInSchool(string $table, string $column, ?int $ignoreId = null): Unique
    {
        $rule = Rule::unique($table, $column)->where('school_id', app(TenantContext::class)->schoolId());

        return $ignoreId ? $rule->ignore($ignoreId) : $rule;
    }
}
