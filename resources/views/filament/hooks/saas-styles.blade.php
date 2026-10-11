@php
    $saasAdminCssPath = resource_path('css/saas-admin.css');
    $saasAdminCss = is_file($saasAdminCssPath) ? file_get_contents($saasAdminCssPath) : '';
@endphp
@if ($saasAdminCss !== '')
    <style>
        {!! $saasAdminCss !!}
    </style>
@endif
