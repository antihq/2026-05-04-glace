@props([
    'variant' => 'outline',
])

@php
    if ($variant === 'solid') {
        throw new \Exception('The "solid" variant is not supported in Lucide.');
    }

    $classes = Flux::classes('shrink-0')->add(
        match ($variant) {
            'outline' => '[:where(&)]:size-6',
            'solid' => '[:where(&)]:size-6',
            'mini' => '[:where(&)]:size-5',
            'micro' => '[:where(&)]:size-4',
        },
    );

    $strokeWidth = match ($variant) {
        'outline' => 2,
        'mini' => 2.25,
        'micro' => 2.5,
    };
@endphp

<svg
    {{ $attributes->class($classes) }}
    data-flux-icon
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="{{ $strokeWidth }}"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
    data-slot="icon"
>
    <path d="M9 10.5L11 12.5L15.5 8M20 22V10C20 7.19974 20 5.79961 19.455 4.73005C18.9757 3.78924 18.2108 3.02433 17.27 2.54497C16.2004 2 14.8003 2 12 2C9.19974 2 7.79961 2 6.73005 2.54497C5.78924 3.02433 5.02433 3.78924 4.54497 4.73005C4 5.79961 4 7.19974 4 10V22L6.27567 20.8622C8.37458 19.8127 9.42404 19.288 10.5248 19.0815C11.4998 18.8985 12.5002 18.8985 13.4752 19.0815C14.576 19.288 15.6254 19.8127 17.7243 20.8622L20 22Z" />
</svg>
