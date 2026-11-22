@inject('kopAssets', 'App\Services\Documents\KopAssets')
@php
    $kopLines = $kopAssets->lines();
    $dinasLogo = $kopAssets->dinasLogo();
    $facilityLogo = $kopAssets->facilityLogo($distribution->facility ?? null);
@endphp

<table class="kop">
    <tr>
        <td class="kop-logo">
            @if ($dinasLogo)
                <img src="{{ $dinasLogo }}" alt="Logo Dinas Kesehatan">
            @endif
        </td>
        <td class="kop-text">
            <div class="kop-line1">{{ $kopLines['line1'] }}</div>
            <div class="kop-line2">{{ $kopLines['line2'] }}</div>
            <div class="kop-line3">{{ $kopLines['line3'] }}</div>
        </td>
        <td class="kop-logo">
            @if ($facilityLogo)
                <img src="{{ $facilityLogo }}" alt="Logo Puskesmas">
            @endif
        </td>
    </tr>
</table>
<div class="kop-rule"></div>
