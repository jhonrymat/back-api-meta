<?php

use Illuminate\Validation\Rules\Can;

return [

    /*
    |--------------------------------------------------------------------------
    | Title
    |--------------------------------------------------------------------------
    |
    | Here you can change the default title of your admin panel.
    |
    | For detailed instructions you can look the title section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'title' => 'Maddi Go',
    'title_prefix' => '',
    'title_postfix' => '',

    /*
    |--------------------------------------------------------------------------
    | Favicon
    |--------------------------------------------------------------------------
    |
    | Here you can activate the favicon.
    |
    | For detailed instructions you can look the favicon section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'use_ico_only' => false,
    'use_full_favicon' => false,

    /*
    |--------------------------------------------------------------------------
    | Google Fonts
    |--------------------------------------------------------------------------
    |
    | Here you can allow or not the use of external google fonts. Disabling the
    | google fonts may be useful if your admin panel internet access is
    | restricted somehow.
    |
    | For detailed instructions you can look the google fonts section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'google_fonts' => [
        'allowed' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Panel Logo
    |--------------------------------------------------------------------------
    |
    | Here you can change the logo of your admin panel.
    |
    | For detailed instructions you can look the logo section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'logo' => '<b>Maddi</b>Go',
    'logo_img' => 'vendor/adminlte/dist/img/maddi-go.png',
    'logo_img_class' => 'brand-image img-circle elevation-3',
    'logo_img_xl' => null,
    'logo_img_xl_class' => 'brand-image-xs',
    'logo_img_alt' => 'Admin Logo',

    /*
    |--------------------------------------------------------------------------
    | Authentication Logo
    |--------------------------------------------------------------------------
    |
    | Here you can setup an alternative logo to use on your login and register
    | screens. When disabled, the admin panel logo will be used instead.
    |
    | For detailed instructions you can look the auth logo section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'auth_logo' => [
        'enabled' => false,
        'img' => [
            'path' => 'vendor/adminlte/dist/img/maddi-go.png',
            'alt' => 'Auth Logo',
            'class' => '',
            'width' => 50,
            'height' => 50,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Preloader Animation
    |--------------------------------------------------------------------------
    |
    | Here you can change the preloader animation configuration.
    |
    | For detailed instructions you can look the preloader section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'preloader' => [
        'enabled' => true,
        'img' => [
            'path' => 'vendor/adminlte/dist/img/maddi-go.png',
            'alt' => 'AdminLTE Preloader Image',
            'effect' => 'animation__shake',
            'width' => 60,
            'height' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Menu
    |--------------------------------------------------------------------------
    |
    | Here you can activate and change the user menu.
    |
    | For detailed instructions you can look the user menu section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'usermenu_enabled' => true,
    'usermenu_header' => false,
    'usermenu_header_class' => 'bg-primary',
    'usermenu_image' => false,
    'usermenu_desc' => false,
    'usermenu_profile_url' => false,

    /*
    |--------------------------------------------------------------------------
    | Layout
    |--------------------------------------------------------------------------
    |
    | Here we change the layout of your admin panel.
    |
    | For detailed instructions you can look the layout section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Layout-and-Styling-Configuration
    |
    */

    'layout_topnav' => null,
    'layout_boxed' => null,
    'layout_fixed_sidebar' => true,
    'layout_fixed_navbar' => true,
    'layout_fixed_footer' => true,
    'layout_dark_mode' => null,

    /*
    |--------------------------------------------------------------------------
    | Authentication Views Classes
    |--------------------------------------------------------------------------
    |
    | Here you can change the look and behavior of the authentication views.
    |
    | For detailed instructions you can look the auth classes section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Layout-and-Styling-Configuration
    |
    */

    'classes_auth_card' => 'card-outline card-primary',
    'classes_auth_header' => '',
    'classes_auth_body' => '',
    'classes_auth_footer' => '',
    'classes_auth_icon' => '',
    'classes_auth_btn' => 'btn-flat btn-primary',

    /*
    |--------------------------------------------------------------------------
    | Admin Panel Classes
    |--------------------------------------------------------------------------
    |
    | Here you can change the look and behavior of the admin panel.
    |
    | For detailed instructions you can look the admin panel classes here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Layout-and-Styling-Configuration
    |
    */

    'classes_body' => '',
    'classes_brand' => 'bg-white',
    'classes_brand_text' => '',
    'classes_content_wrapper' => '',
    'classes_content_header' => '',
    'classes_content' => '',
    'classes_sidebar' => 'sidebar-dark-primary elevation-4',
    'classes_sidebar_nav' => 'nav nav-pills nav-sidebar flex-column nav-child-indent',
    'classes_topnav' => 'navbar-dark navbar-light',
    'classes_topnav_nav' => 'navbar-expand',
    'classes_topnav_container' => 'container',

    /*
    |--------------------------------------------------------------------------
    | Sidebar
    |--------------------------------------------------------------------------
    |
    | Here we can modify the sidebar of the admin panel.
    |
    | For detailed instructions you can look the sidebar section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Layout-and-Styling-Configuration
    |
    */

    'sidebar_mini' => 'lg',
    'sidebar_collapse' => false,
    'sidebar_collapse_auto_size' => false,
    'sidebar_collapse_remember' => false,
    'sidebar_collapse_remember_no_transition' => true,
    'sidebar_scrollbar_theme' => 'os-theme-light',
    'sidebar_scrollbar_auto_hide' => 'l',
    'sidebar_nav_accordion' => true,
    'sidebar_nav_animation_speed' => 300,

    /*
    |--------------------------------------------------------------------------
    | Control Sidebar (Right Sidebar)
    |--------------------------------------------------------------------------
    |
    | Here we can modify the right sidebar aka control sidebar of the admin panel.
    |
    | For detailed instructions you can look the right sidebar section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Layout-and-Styling-Configuration
    |
    */

    'right_sidebar' => false,
    'right_sidebar_icon' => 'fas fa-cogs',
    'right_sidebar_theme' => 'dark',
    'right_sidebar_slide' => true,
    'right_sidebar_push' => true,
    'right_sidebar_scrollbar_theme' => 'os-theme-light',
    'right_sidebar_scrollbar_auto_hide' => 'l',

    /*
    |--------------------------------------------------------------------------
    | URLs
    |--------------------------------------------------------------------------
    |
    | Here we can modify the url settings of the admin panel.
    |
    | For detailed instructions you can look the urls section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'use_route_url' => false,
    'dashboard_url' => 'home',
    'logout_url' => 'logout',
    'login_url' => 'login',
    'register_url' => false,
    // 'register_url' => 'register',
    'password_reset_url' => 'password/reset',
    'password_email_url' => 'password/email',
    'profile_url' => false,

    /*
    |--------------------------------------------------------------------------
    | Laravel Mix
    |--------------------------------------------------------------------------
    |
    | Here we can enable the Laravel Mix option for the admin panel.
    |
    | For detailed instructions you can look the laravel mix section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Other-Configuration
    |
    */

    'enabled_laravel_mix' => false,
    'laravel_mix_css_path' => 'css/app.css',
    'laravel_mix_js_path' => 'js/app.js',

    /*
    |--------------------------------------------------------------------------
    | Menu Items
    |--------------------------------------------------------------------------
    |
    | Here we can modify the sidebar/top navigation of the admin panel.
    |
    | For detailed instructions you can look here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Menu-Configuration
    |
    */

    'menu' => [
        // Navbar items:

        [
            'type' => 'navbar-search',
            'text' => 'search',
            'topnav_right' => false,
        ],
        [
            'type' => 'fullscreen-widget',
            'topnav_right' => true,
        ],

        // Sidebar items:

        [
            'text' => 'blog',
            'url' => 'admin/blog',
            'can' => 'manage-blog',
        ],
        // Contactos Section
        [
            'text' => 'Contactos',
            'icon' => 'fas fa-fw fa-user',
            'submenu' => [
                [
                    'text' => 'Consultar',
                    'route' => 'contact.index',
                    'can' => 'contactos.index',
                    'icon' => 'fas fa-fw fa-users',
                    'target' => '_blank', // Esta clave abre el enlace en una nueva pestaña
                ],
                [
                    'text' => 'Gestionar',
                    'route' => 'contactos.index',
                    'can' => 'contactos.index',
                    'icon' => 'fas fa-fw fa-address-book',
                ],
                [
                    'text' => 'Campos personalizados',
                    'route' => 'custom_fields.index',
                    'can' => 'custom_fields.index',
                    'icon' => 'fas fa-fw fa-tags',
                ],
                [
                    'text' => 'Etiquetas',
                    'route' => 'tags.index',
                    'can' => 'tags.index',
                    'icon' => 'fas fa-fw fa-tags',
                ],
            ],
        ],
        [
            'text' => 'Gestión WhatsApp',
            'icon' => 'fas fa-fw fa-layer-group',
            'id' => 'menu-gestion-1',
            'submenu' => [
                [
                    'text' => 'WhatsApp',
                    'icon' => 'fab fa-fw fa-whatsapp',
                    'id' => 'menu-whatsapp-2',
                    'submenu' => [
                        [
                            'text' => 'Chats',
                            'route' => 'admin.chat',
                            'can' => 'admin.chat',
                            'icon' => 'fab fa-fw fa-whatsapp',
                            'id' => 'menu-chats-3',
                        ],
                        [
                            'text' => 'Plantillas',
                            'route' => 'plantillas',
                            'can' => 'plantillas',
                            'icon' => 'fas fa-fw fa-file-invoice',
                        ],
                        [
                            'text' => 'Programados',
                            'route' => 'programados',
                            'can' => 'programados',
                            'icon' => 'fas fa-fw fa-clock',
                        ],
                    ],
                ],

                // Contratación Local Section
                [
                    'text' => 'Contratación Local',
                    'icon' => 'fas fa-fw fa-check',
                    'submenu' => [
                        [
                            'text' => 'Solicitudes',
                            'route' => 'solicitudes',
                            'can' => 'solicitudes',
                            'icon' => 'fab fa-fw fa-readme',
                        ],
                    ],
                ],
                // Informes Section
                [
                    'text' => 'Análisis',
                    'icon' => 'fas fa-fw fa-flag',
                    'submenu' => [
                        [
                            'text' => 'Resumen',
                            'route' => 'estadisticas',
                            'can' => 'estadisticas',
                            'icon' => 'fas fa-fw fa-chart-line',
                        ],
                        [
                            'text' => 'Masivos',
                            'route' => 'envios-plantillas',
                            'can' => 'envios-plantillas',
                            'icon' => 'fas fa-fw fa-history',
                            'label' => 'Nuevo',
                            'label_color' => 'success',
                        ],
                        [
                            'text' => 'Diarios',
                            'route' => 'conteo.por-dia',
                            'can' => 'plantillas',
                            'icon' => 'fas fa-fw fa-calendar-day',
                        ],
                        [
                            'text' => 'Destinatarios',
                            'route' => 'envios',
                            'can' => 'estadisticas',
                            'icon' => 'fas fa-fw fa-users',
                            'target' => '_blank',
                        ],
                    ],
                ],
                [
                    'text' => 'Agentes IA',
                    'icon' => 'fas fa-fw fa-robot',
                    'submenu' => [
                        [
                            'text' => 'Gestinar IA',
                            'route' => 'bots.index',
                            'icon' => 'fas fa-fw fa-robot',
                        ],
                        [
                            'text' => 'Leads',
                            'route' => 'leads',
                            'icon' => 'fas fa-fw fa-headset',
                        ]
                    ],
                ],
                [
                    'text' => 'Configuración',
                    'icon' => 'fas fa-fw fa-wrench',
                    'submenu' => [
                        [
                            'text' => 'Aplicaciones',
                            'route' => 'aplicaciones.index',
                            'can' => 'aplicaciones.index',
                            'icon' => 'fas fa-fw fa-window-restore',
                        ],
                        [
                            'text' => 'Numeros',
                            'route' => 'numeros.index',
                            'can' => 'numeros.index',
                            'icon' => 'fas fa-fw fa-mobile',
                        ],
                    ],
                ],
            ],
        ],
        [
            'text' => 'Gestión Correos',
            'icon' => 'fas fa-fw fa-envelope-open-text', // Puedes cambiar el ícono
            'submenu' => [
                [
                    'text' => 'Grupos Mails',
                    'route' => 'groups.index',
                    'can' => 'groups.index',
                    'icon' => 'fas fa-fw fa-users',
                ],
                [
                    'text' => 'Boletines',
                    'route' => 'newsletters.index',
                    'can' => 'newsletters.index',
                    'icon' => 'fas fa-fw fa-newspaper',
                ],
                [
                    'text' => 'Plantillas',
                    'route' => 'email-templates.index',
                    'can' => 'email-templates.index',
                    'icon' => 'fas fa-fw fa-file-invoice',
                ]
                ,
                [
                    'text' => 'envios masivos',
                    'route' => 'newsletters.masivos',
                    'icon' => 'fas fa-fw fa-poll',
                    'label' => 'Nuevo',  // Aquí se agrega el tag
                    'label_color' => 'success',  // Aquí se puede elegir el color del tag (puede ser 'success', 'warning', 'danger', etc.)
                ],
                // [
                //     'text' => 'Reportes',
                //     'route' => 'programados',
                //     'icon' => 'fas fa-fw fa-chart-bar',
                // ],
                // [
                //     'text' => 'Busqueda de mails',
                //     'route' => 'programados',
                //     'icon' => 'fas fa-fw fa-search',
                // ],
                // [
                //     'text' => 'Mails Bloqueados',
                //     'route' => 'programados',
                //     'icon' => 'fas fa-fw fa-inbox',
                // ],
            ],
        ],
        [
            'text' => 'Administración',
            'icon' => 'fas fa-fw fa-cogs',
            'submenu' => [
                [
                    'text' => 'Usuarios',
                    'route' => 'users.index',
                    'can' => 'users.index',
                    'icon' => 'fas fa-fw fa-users',
                ],
                [
                    'text' => 'Roles',
                    'route' => 'roles.index',
                    'can' => 'roles.index',
                    'icon' => 'fas fa-fw fa-window-restore',
                ],
                [
                    'text' => 'Permisos',
                    'route' => 'permisos.index',
                    'can' => 'permisos.index',
                    'icon' => 'fas fa-fw fa-mobile',
                ],
                [
                    'text' => 'Logs',
                    'url' => 'log-viewer',
                    'can' => 'log-viewer',
                    'icon' => 'fas fa-exclamation-triangle',
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Menu Filters
    |--------------------------------------------------------------------------
    |
    | Here we can modify the menu filters of the admin panel.
    |
    | For detailed instructions you can look the menu filters section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Menu-Configuration
    |
    */

    'filters' => [
        JeroenNoten\LaravelAdminLte\Menu\Filters\GateFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\HrefFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\SearchFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\ActiveFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\ClassesFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\LangFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\DataFilter::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Plugins Initialization
    |--------------------------------------------------------------------------
    |
    | Here we can modify the plugins used inside the admin panel.
    |
    | For detailed instructions you can look the plugins section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Plugins-Configuration
    |
    */

    'plugins' => [
        'Datatables' => [
            'active' => true,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdn.datatables.net/1.10.19/js/dataTables.bootstrap4.min.js',
                ],
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => '//cdn.datatables.net/1.10.19/css/dataTables.bootstrap4.min.css',
                ],
            ],
        ],
        'Select2' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js',
                ],
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.css',
                ],
            ],
        ],
        'Chartjs' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.0/Chart.bundle.min.js',
                ],
            ],
        ],
        'Sweetalert2' => [
            'active' => true,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdn.jsdelivr.net/npm/sweetalert2@11',
                ],
            ],
        ],
        'Pace' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/pace/1.0.2/themes/blue/pace-theme-center-radar.min.css',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/pace/1.0.2/pace.min.js',
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | IFrame
    |--------------------------------------------------------------------------
    |
    | Here we change the IFrame mode configuration. Note these changes will
    | only apply to the view that extends and enable the IFrame mode.
    |
    | For detailed instructions you can look the iframe mode section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/IFrame-Mode-Configuration
    |
    */

    'iframe' => [
        'default_tab' => [
            'url' => null,
            'title' => null,
        ],
        'buttons' => [
            'close' => true,
            'close_all' => true,
            'close_all_other' => true,
            'scroll_left' => true,
            'scroll_right' => true,
            'fullscreen' => true,
        ],
        'options' => [
            'loading_screen' => 1000,
            'auto_show_new_tab' => true,
            'use_navbar_items' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Livewire
    |--------------------------------------------------------------------------
    |
    | Here we can enable the Livewire support.
    |
    | For detailed instructions you can look the livewire here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Other-Configuration
    |
    */

    'livewire' => true,
];
