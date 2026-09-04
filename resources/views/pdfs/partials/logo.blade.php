{{-- Company Logo for PDF documents (DomPDF compatible) --}}
@php
    $vultrBase = env('VULTR_ENDPOINT', 'https://blr1.vultrobjects.com') . '/' . env('VULTR_BUCKET', 'space-1') . '/';
    $logoSrc = null;

    // Priority 1: Company logo from DB (stored on Vultr)
    if (!empty($company->logo_path)) {
        $logoSrc = str_starts_with($company->logo_path, 'http')
            ? $company->logo_path
            : $vultrBase . $company->logo_path;
    }

    // Priority 2: Default Trumac logo from public path (no base64, DomPDF reads file directly)
    if (!$logoSrc) {
        $jpgLogo = public_path('images/logo-pdf.jpg');
        if (file_exists($jpgLogo)) {
            $logoSrc = $jpgLogo;
        }
    }
@endphp

@if($logoSrc)
    <img src="{{ $logoSrc }}" alt="{{ $company->name ?? 'Trumac' }}" style="height: 36px; width: 36px; border-radius: 4px; vertical-align: middle;" />
    <span style="font-size: 16px; font-weight: bold; color: {{ $logoColor ?? '#1a237e' }}; letter-spacing: 1px; vertical-align: middle; margin-left: 8px;">{{ $company->name ?? 'Trumac' }}</span>
@else
    <span style="font-size: 22px; font-weight: bold; color: {{ $logoColor ?? '#1a237e' }}; letter-spacing: 2px;">{{ strtoupper($company->name ?? 'TRUMAC') }}</span>
@endif
