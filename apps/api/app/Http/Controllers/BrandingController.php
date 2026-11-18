<?php

namespace App\Http\Controllers;

use App\Http\Requests\BrandingRequest;
use App\Models\SchoolBranding;
use App\Support\Audit;
use App\Support\Entitlements;
use App\Tenancy\TenantContext;

class BrandingController extends Controller
{
    private const FIELDS = ['app_name', 'primary_color', 'login_title', 'login_message', 'report_header', 'public_page_title'];

    public function show(TenantContext $context)
    {
        abort_unless($context->can('branding.manage'), 403);

        return ['data' => [
            'entitled' => Entitlements::has($context->schoolId(), 'white_label'),
            'branding' => SchoolBranding::where('school_id', $context->schoolId())->first()?->only(self::FIELDS),
        ]];
    }

    public function update(BrandingRequest $request, TenantContext $context)
    {
        abort_unless($context->can('branding.manage'), 403);
        abort_unless(Entitlements::has($context->schoolId(), 'white_label'), 402, 'White Label memerlukan paket Pro.');

        $branding = SchoolBranding::firstOrCreate(['school_id' => $context->schoolId()]);
        $branding->update($request->validated());
        Audit::log('branding.updated', $branding);

        return ['data' => ['entitled' => true, 'branding' => $branding->only(self::FIELDS)]];
    }
}
