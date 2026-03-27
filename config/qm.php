<?php

return [
    'name' => 'QM',
    'description' => 'Quality Management Checklisten Module',
    'version' => '1.0.0',

    'routing' => [
        'prefix' => 'qm',
        'middleware' => ['web', 'auth'],
    ],

    'guard' => 'web',

    'navigation' => [
        'main' => [
            'qm' => [
                'title' => 'QM',
                'icon' => 'heroicon-o-clipboard-document-check',
                'route' => 'qm.dashboard',
            ],
        ],
    ],

    'sidebar' => [
        'qm' => [
            'title' => 'Quality Management',
            'icon' => 'heroicon-o-clipboard-document-check',
            'items' => [
                'dashboard' => [
                    'title' => 'Dashboard',
                    'route' => 'qm.dashboard',
                    'icon' => 'heroicon-o-home',
                ],
                'templates' => [
                    'title' => 'Templates',
                    'route' => 'qm.templates.index',
                    'icon' => 'heroicon-o-document-duplicate',
                ],
                'instances' => [
                    'title' => 'Checklisten',
                    'route' => 'qm.instances.index',
                    'icon' => 'heroicon-o-clipboard-document-list',
                ],
                'deviations' => [
                    'title' => 'Abweichungen',
                    'route' => 'qm.deviations.index',
                    'icon' => 'heroicon-o-exclamation-triangle',
                ],
            ],
        ],
    ],
];
