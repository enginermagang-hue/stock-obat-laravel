@props(['name'])

<i {{ $attributes->merge([
    'class' => 'ri-'.$name.' os-icon',
    'aria-hidden' => 'true',
]) }}></i>
