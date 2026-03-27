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
                'sections' => [
                    'title' => 'Sektionen',
                    'route' => 'qm.sections.index',
                    'icon' => 'heroicon-o-rectangle-group',
                ],
                'field-definitions' => [
                    'title' => 'Feld-Definitionen',
                    'route' => 'qm.field-definitions.index',
                    'icon' => 'heroicon-o-adjustments-horizontal',
                ],
                'field-types' => [
                    'title' => 'Feldtypen',
                    'route' => 'qm.field-types.index',
                    'icon' => 'heroicon-o-cube',
                ],
            ],
        ],
    ],
];
