<?php
/**
 * Rooted — Menu route registry.
 * Maps stable route keys → URL paths. Update URLs here when they change;
 * menus that reference a route_key resolve dynamically at render time.
 * Never store raw URLs in storage/menus.json for built-in pages — use route_key.
 */
return [
    // Dashboard
    'dashboard'           => ['url' => '/dashboard',           'label' => 'Dashboard',          'group' => 'Dashboard'],
    'dashboard.map'       => ['url' => '/dashboard/map',       'label' => 'Map',                'group' => 'Dashboard'],
    'dashboard.overview'  => ['url' => '/dashboard/overview',  'label' => 'Overview',           'group' => 'Dashboard'],
    'dashboard.nearby'    => ['url' => '/dashboard/nearby',    'label' => 'Nearby',             'group' => 'Dashboard'],
    'dashboard.reports'   => ['url' => '/dashboard/reports',   'label' => 'Reports',            'group' => 'Dashboard'],
    // Items
    'items'               => ['url' => '/items',               'label' => 'Items',              'group' => 'Items'],
    'items.create'        => ['url' => '/items/create',        'label' => 'Add Item',           'group' => 'Items'],
    'photos'              => ['url' => '/photos/quick',        'label' => 'Quick Photos',       'group' => 'Items'],
    // Garden
    'garden'              => ['url' => '/garden',              'label' => 'Garden Hub',         'group' => 'Garden'],
    'garden.biodynamic'   => ['url' => '/garden/biodynamic',   'label' => 'Lunar Calendar',     'group' => 'Garden'],
    'seeds'               => ['url' => '/seeds',               'label' => 'Seeds Catalog',      'group' => 'Garden'],
    'seeds.create'        => ['url' => '/seeds/create',        'label' => 'Add Seed',           'group' => 'Garden'],
    'seeds.family-needs'  => ['url' => '/seeds/family-needs',  'label' => 'Family Needs',       'group' => 'Garden'],
    'seeds.buy-list'      => ['url' => '/seeds/buy-list',      'label' => 'Buy List',           'group' => 'Garden'],
    // Tasks
    'tasks'               => ['url' => '/tasks',               'label' => 'Tasks',              'group' => 'Tasks'],
    'tasks.achats'        => ['url' => '/tasks?tab=achats',    'label' => 'Tasks — Purchases',  'group' => 'Tasks'],
    'tasks.irrigation'    => ['url' => '/tasks?tab=irrigation','label' => 'Tasks — Irrigation', 'group' => 'Tasks'],
    'tasks.reminders'     => ['url' => '/tasks?tab=reminders', 'label' => 'Tasks — Reminders',  'group' => 'Tasks'],
    'reminders'           => ['url' => '/reminders',           'label' => 'Reminders',          'group' => 'Tasks'],
    'irrigation'          => ['url' => '/irrigation',          'label' => 'Irrigation',         'group' => 'Tasks'],
    // Harvest & Finance
    'harvest'             => ['url' => '/harvest/quick',       'label' => 'Harvest',            'group' => 'Harvest'],
    'finance'             => ['url' => '/finance',             'label' => 'Finance',            'group' => 'Finance'],
    // Logs & Settings
    'activity'            => ['url' => '/activity-log',        'label' => 'Activity Log',       'group' => 'Logs'],
    'logs.errors'         => ['url' => '/logs/errors',         'label' => 'Error Log',          'group' => 'Logs'],
    'settings'            => ['url' => '/settings',            'label' => 'Settings',           'group' => 'Settings'],
    'settings.storage'    => ['url' => '/settings/storage',    'label' => 'Storage',            'group' => 'Settings'],
    'settings.harvest'    => ['url' => '/settings/harvest',    'label' => 'Harvest Settings',   'group' => 'Settings'],
    'settings.pwa'        => ['url' => '/settings/pwa',        'label' => 'PWA Settings',       'group' => 'Settings'],
    // Other
    'privacy'             => ['url' => '/privacy',             'label' => 'Privacy Policy',     'group' => 'Other'],
];
