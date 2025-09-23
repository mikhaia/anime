@props(['name'])

@php
    $icons = [
        'search' => [
            'paths' => [
                [
                    'd' => 'm21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z',
                    'stroke-linecap' => 'round',
                    'stroke-linejoin' => 'round',
                ],
            ],
        ],
        'favorite' => [
            'paths' => [
                [
                    'd' => 'M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z',
                    'stroke-linecap' => 'round',
                    'stroke-linejoin' => 'round',
                ],
            ],
        ],
        'star' => [
            'paths' => [
                [
                    'd' => 'M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z',
                    'stroke-linecap' => 'round',
                    'stroke-linejoin' => 'round',
                ],
            ],
        ],
        'sparkles' => [
            'paths' => [
                [
                    'd' => 'M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z',
                    'stroke-linecap' => 'round',
                    'stroke-linejoin' => 'round',
                ],
            ],
        ],
        'user' => [
            'paths' => [
                [
                    'd' => 'M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z',
                    'stroke-linecap' => 'round',
                    'stroke-linejoin' => 'round',
                ],
            ],
        ],
        'users' => [
            'paths' => [
                [
                    'd' => 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z',
                    'stroke-linecap' => 'round',
                    'stroke-linejoin' => 'round',
                ],
            ],
        ],
        'chevron-down' => [
            'paths' => [
                [
                    'd' => 'm19.5 8.25-7.5 7.5-7.5-7.5',
                    'stroke-linecap' => 'round',
                    'stroke-linejoin' => 'round',
                ],
            ],
        ],
        'chevron-left' => [
            'paths' => [
                [
                    'd' => 'M15.75 19.5 8.25 12l7.5-7.5',
                    'stroke-linecap' => 'round',
                    'stroke-linejoin' => 'round',
                ],
            ],
        ],
        'chevron-right' => [
            'paths' => [
                [
                    'd' => 'm8.25 4.5 7.5 7.5-7.5 7.5',
                    'stroke-linecap' => 'round',
                    'stroke-linejoin' => 'round',
                ],
            ],
        ],
        'pencil-square' => [
            'paths' => [
                [
                    'd' => 'm16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10',
                    'stroke-linecap' => 'round',
                    'stroke-linejoin' => 'round',
                ],
            ],
        ],
        'arrow-right-on-rectangle' => [
            'paths' => [
                [
                    'd' => 'M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9',
                    'stroke-linecap' => 'round',
                    'stroke-linejoin' => 'round',
                ],
            ],
        ],
        'arrow-left-on-rectangle' => [
            'paths' => [
                [
                    'd' => 'M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75',
                    'stroke-linecap' => 'round',
                    'stroke-linejoin' => 'round',
                ],
            ],
        ],
        'arrows-pointing-out' => [
            'paths' => [
                [
                    'd' => 'M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15',
                    'stroke-linecap' => 'round',
                    'stroke-linejoin' => 'round',
                ],
            ],
        ],
        'arrows-pointing-in' => [
            'paths' => [
                [
                    'd' => 'M9 9V4.5M9 9H4.5M9 9 3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5 5.25 5.25',
                    'stroke-linecap' => 'round',
                    'stroke-linejoin' => 'round',
                ],
            ],
        ],
        'x-mark' => [
            'paths' => [
                [
                    'd' => 'M6 18 18 6M6 6l12 12',
                    'stroke-linecap' => 'round',
                    'stroke-linejoin' => 'round',
                ],
            ],
        ],
        'plus' => [
            'paths' => [
                [
                    'd' => 'M12 4.5v15m7.5-7.5h-15',
                    'stroke-linecap' => 'round',
                    'stroke-linejoin' => 'round',
                ],
            ],
        ],
        'document-check' => [
            'paths' => [
                [
                    'd' => 'M10.125 2.25h-4.5c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125v-9M10.125 2.25h.375a9 9 0 0 1 9 9v.375M10.125 2.25A3.375 3.375 0 0 1 13.5 5.625v1.5c0 .621.504 1.125 1.125 1.125h1.5a3.375 3.375 0 0 1 3.375 3.375M9 15l2.25 2.25L15 12',
                    'stroke-linecap' => 'round',
                    'stroke-linejoin' => 'round',
                ],
            ],
        ],
    ];

    $icon = $icons[$name] ?? null;
@endphp

@if($icon)
    @php
        $svgAttributes = $attributes
            ->merge([
                'xmlns' => 'http://www.w3.org/2000/svg',
                'viewBox' => $icon['viewBox'] ?? '0 0 24 24',
                'fill' => $icon['fill'] ?? 'none',
                'stroke' => $icon['stroke'] ?? 'currentColor',
                'stroke-width' => $icon['strokeWidth'] ?? '1.5',
                'aria-hidden' => 'true',
            ])
            ->class(['icon', $icon['class'] ?? null]);
    @endphp
    <svg {{ $svgAttributes }}>
        @foreach($icon['paths'] as $path)
            <path @foreach($path as $attribute => $value) {{ $attribute }}="{{ $value }}" @endforeach></path>
        @endforeach
    </svg>
@endif
