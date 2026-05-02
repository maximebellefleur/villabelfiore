<?php
/**
 * Rooted — Default menu structure.
 * Used as fallback when storage/menus.json does not exist.
 * Mirrors the menus that were previously hardcoded in nav.php.
 *
 * Item fields:
 *   id        — unique string, stable across saves
 *   type      — 'route' (built-in page) | 'custom' (user-defined URL)
 *   route_key — key in config/menu_routes.php (type=route only)
 *   url       — raw URL (type=custom only)
 *   label     — display text
 *   icon      — key in config/menu_icons.php, or null
 *   icon_svg  — custom SVG inner path (when icon is null or 'custom')
 *   children  — nested items (max 1 level)
 */
return [
    'main' => [
        ['id'=>'d_dashboard', 'type'=>'route', 'route_key'=>'dashboard',        'label'=>'Dashboard',  'icon'=>'dashboard',  'icon_svg'=>null, 'children'=>[]],
        ['id'=>'d_map',       'type'=>'route', 'route_key'=>'dashboard.map',    'label'=>'Map',        'icon'=>'map',        'icon_svg'=>null, 'children'=>[]],
        ['id'=>'d_items',     'type'=>'route', 'route_key'=>'items',            'label'=>'Items',      'icon'=>'items',      'icon_svg'=>null, 'children'=>[]],
        ['id'=>'d_garden',    'type'=>'route', 'route_key'=>'garden',           'label'=>'Garden',     'icon'=>'garden',     'icon_svg'=>null, 'children'=>[
            ['id'=>'d_g_seed_new',   'type'=>'route', 'route_key'=>'seeds.create',      'label'=>'＋ Seed',      'icon'=>'seed',   'icon_svg'=>null, 'children'=>[]],
            ['id'=>'d_g_seeds',      'type'=>'route', 'route_key'=>'seeds',             'label'=>'All Seeds',    'icon'=>'seed',   'icon_svg'=>null, 'children'=>[]],
            ['id'=>'d_g_family',     'type'=>'route', 'route_key'=>'seeds.family-needs','label'=>'Family Needs', 'icon'=>'family', 'icon_svg'=>null, 'children'=>[]],
            ['id'=>'d_g_beds',       'type'=>'route', 'route_key'=>'garden',            'label'=>'Garden Beds',  'icon'=>'garden', 'icon_svg'=>null, 'children'=>[]],
        ]],
        ['id'=>'d_tasks',     'type'=>'route', 'route_key'=>'tasks',            'label'=>'Tasks',      'icon'=>'tasks',      'icon_svg'=>null, 'children'=>[
            ['id'=>'d_t_achats',     'type'=>'route', 'route_key'=>'tasks.achats',     'label'=>'Achats',     'icon'=>'basket',    'icon_svg'=>null, 'children'=>[]],
            ['id'=>'d_t_irrigation', 'type'=>'route', 'route_key'=>'tasks.irrigation', 'label'=>'Irrigation', 'icon'=>'irrigation','icon_svg'=>null, 'children'=>[]],
            ['id'=>'d_t_reminders',  'type'=>'route', 'route_key'=>'tasks.reminders',  'label'=>'Reminders',  'icon'=>'reminders', 'icon_svg'=>null, 'children'=>[]],
        ]],
        ['id'=>'d_harvest',   'type'=>'route', 'route_key'=>'harvest',          'label'=>'Harvest',    'icon'=>'harvest',    'icon_svg'=>null, 'children'=>[]],
        ['id'=>'d_finance',   'type'=>'route', 'route_key'=>'finance',          'label'=>'Finance',    'icon'=>'finance',    'icon_svg'=>null, 'children'=>[]],
        ['id'=>'d_activity',  'type'=>'route', 'route_key'=>'activity',         'label'=>'Activity',   'icon'=>'activity',   'icon_svg'=>null, 'children'=>[]],
        ['id'=>'d_settings',  'type'=>'route', 'route_key'=>'settings',         'label'=>'Settings',   'icon'=>'settings',   'icon_svg'=>null, 'children'=>[]],
    ],
    'footer' => [
        ['id'=>'f_privacy', 'type'=>'route', 'route_key'=>'privacy', 'label'=>'Privacy Policy', 'icon'=>null, 'icon_svg'=>null, 'children'=>[]],
    ],
];
