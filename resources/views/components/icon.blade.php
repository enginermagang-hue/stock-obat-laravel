@props(['name', 'size' => 20])

<i {{ $attributes->merge([
    'class' => 'ri-'.$name.' os-icon os-icon-'.$size,
    'aria-hidden' => 'true',
]) }}></i>
