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
    'logo' => 'MichiSys',
    'logo_img' => 'images/michisys-store.svg',
    'logo_img_class' => 'brand-image elevation-3',
    'logo_img_alt' => 'MichiSys',

    'menu' => [
        [
            'text' => 'Punto de venta',
            'route' => 'sales.create',
            'icon' => 'fas fa-fw fa-cash-register',
            'can' => 'use-sales',
        ],
        [
            'text' => 'Administración de plataforma',
            'route' => 'superadmin.businesses.index',
            'icon' => 'fas fa-fw fa-building',
            'can' => 'manage-platform',
        ],
        [
            'text' => 'Panel Principal',
            'route' => 'dashboard',
            'icon' => 'fas fa-fw fa-tachometer-alt',
            'can' => 'manage-business',
        ],

        [
            'header' => 'GESTIÓN',
        ],

        [
            'text' => 'Inventario',
            'route' => 'products.index',
            'icon' => 'fas fa-fw fa-box-open',
            'can' => 'manage-business',
        ],
        [
            'text' => 'Categorías',
            'route' => 'categories.index',
            'icon' => 'fas fa-fw fa-tags',
            'can' => 'manage-business',
        ],
        [
            'text' => 'Combos',
            'route' => 'combos.index',
            'icon' => 'fas fa-fw fa-layer-group',
            'can' => 'manage-business',
        ],
        [
            'text' => 'Historial de ventas',
            'route' => 'sales.index',
            'icon' => 'fas fa-fw fa-cash-register',
            'can' => 'use-sales',
        ],
        [
            'text' => 'Cuentas por cobrar',
            'route' => 'receivables.index',
            'icon' => 'fas fa-fw fa-file-invoice-dollar',
            'can' => 'use-receivables',
        ],
        [
            'text' => 'Consultar precios',
            'route' => 'prices.index',
            'icon' => 'fas fa-fw fa-search-dollar',
            'can' => 'use-sales',
        ],
        [
            'text' => 'Movimientos y lotes',
            'route' => 'inventory.index',
            'icon' => 'fas fa-fw fa-warehouse',
            'can' => 'manage-business',
        ],
        [
            'text' => 'Clientes',
            'route' => 'clients.index',
            'icon' => 'fas fa-fw fa-user-friends',
            'can' => 'manage-business',
        ],
        [
            'text' => 'Proveedores',
            'route' => 'providers.index',
            'icon' => 'fas fa-fw fa-truck',
            'can' => 'manage-business',
        ],
        [
            'text' => 'Compras',
            'route' => 'purchases.index',
            'icon' => 'fas fa-fw fa-cart-plus',
            'can' => 'manage-business',
        ],
        [
            'text' => 'Recetas',
            'route' => 'recipes.index',
            'icon' => 'fas fa-fw fa-book',
            'can' => 'manage-business',
        ],
        [
            'text' => 'Producción',
            'route' => 'productions.index',
            'icon' => 'fas fa-fw fa-industry',
            'can' => 'manage-business',
        ],
        [
            'text' => 'Cajeros',
            'route' => 'users.index',
            'icon' => 'fas fa-fw fa-users-cog',
            'can' => 'manage-business',
        ],
        [
            'text' => 'Datos del negocio',
            'route' => 'business.settings.edit',
            'icon' => 'fas fa-fw fa-store',
            'can' => 'manage-business',
        ],
        [
            'text' => 'Reportes',
            'route' => 'reports.index',
            'icon' => 'fas fa-fw fa-file-pdf',
            'can' => 'manage-business',
        ],
    ],
];