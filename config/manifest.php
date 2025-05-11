<?php

return [
    'id' => '0123456789',
    'dir' => 'ltr',
    'lang' => config('app.locale'),
    'name' => config('app.name'),
    'scope' => '/',
    'display' => 'standalone',
    'start_url' => '/',
    'short_name' => config('app.name'),
    'theme_color' => '#000',
    'background_color' => '#000',
    'description' => config('app.name'),
    'orientation' => 'portrait',
    'related_applications' => [],
    'prefer_related_applications' => false,
    'display_override' => [
        "window-controls-overlay"
    ],
    'screenshots' => [],
    'features' => [
        "Cross Platform",
        "low-latency inking",
        "fast",
        "useful AI"
    ],
    "icons" => [
        [
            "src" => "icons/manifest-icon-192.maskable.jpg",
            "sizes" => "192x192",
            "type" => "image/png",
            "purpose" => "any"
        ],
        [
            "src" => "icons/manifest-icon-512.maskable.jpg",
            "sizes" => "512x512",
            "type" => "image/png",
            "purpose" => "any"
        ]
    ],
    "categories" => [
        "productivity"
    ],
    "launch_handler" => [
        "client_mode" => "navigate-existing"
    ],
    "edge_side_panel" => [],
    "shortcuts" => []
];
