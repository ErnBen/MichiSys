<?php

return [
    'title' => 'MichiSys',
    'title_prefix' => '',
    'title_postfix' => '',

    'use_route_url' => false,
    'dashboard_url' => 'dashboard',
    'logout_url' => 'logout',
    'login_url' => 'login',
    'register_url' => 'register',

    'menu' => [
        [
            'text' => 'Panel Principal',
            'route' => 'dashboard',
            'icon' => 'fas fa-fw fa-tachometer-alt',
        ],

        [
            'header' => 'GESTIÓN',
        ],

        [
            'text' => 'Productos',
            'route' => 'products.index',
            'icon' => 'fas fa-fw fa-box-open',
        ],
        [
            'text' => 'Categorías',
            'route' => 'categories.index',
            'icon' => 'fas fa-fw fa-tags',
        ],
        [
            'text' => 'Combos',
            'route' => 'combos.index',
            'icon' => 'fas fa-fw fa-layer-group',
        ],
        [
            'text' => 'Ventas',
            'route' => 'sales.index',
            'icon' => 'fas fa-fw fa-cash-register',
        ],
        [
            'text' => 'Inventario',
            'route' => 'inventory.index',
            'icon' => 'fas fa-fw fa-warehouse',
        ],
        [
            'text' => 'Clientes',
            'route' => 'clients.index',
            'icon' => 'fas fa-fw fa-user-friends',
        ],
        [
            'text' => 'Proveedores',
            'route' => 'providers.index',
            'icon' => 'fas fa-fw fa-truck',
        ],
        [
            'text' => 'Usuarios',
            'route' => 'users.index',
            'icon' => 'fas fa-fw fa-users-cog',
        ],
        [
            'text' => 'Reportes',
            'route' => 'reports.index',
            'icon' => 'fas fa-fw fa-file-pdf',
        ],
    ],
];