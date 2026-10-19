<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Inertia Page Resolution
    |--------------------------------------------------------------------------
    |
    | This project stores its Inertia components in resources/js/Pages using
    | the capitalized directory name. Keep the server-side test resolver
    | aligned with the Vite page resolver so Linux CI can locate TS/TSX files.
    |
    */
    'pages' => [
        'ensure_pages_exist' => true,
        'paths' => [
            resource_path('js/Pages'),
        ],
        'extensions' => [
            'js',
            'jsx',
            'ts',
            'tsx',
            'vue',
            'svelte',
        ],
    ],

    'testing' => [
        'ensure_pages_exist' => true,
    ],
];
