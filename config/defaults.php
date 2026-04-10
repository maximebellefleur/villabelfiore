<?php

return [
    'currency'                   => 'EUR',
    'language'                   => 'en',
    'timezone'                   => 'Europe/Rome',
    'storage_driver'             => 'local',
    'backup_mode'                => 'manual',
    'finance_enabled_types'      => ['olive_tree', 'almond_tree'],
    'harvest_enabled_types'      => ['olive_tree', 'almond_tree'],
    'gps_accuracy_threshold_m'   => 20,
    'image_refresh_interval_days'=> 365,
    'reminder_default_lead_days' => 7,
    'version'                    => '1.5.0',
    'version_name'               => 'Weather Widget & Nearby Card Photo Badge',
    'update_zip_url'             => 'https://raw.githubusercontent.com/maximebellefleur/villabelfiore/claude/create-rooted-project-RVbog/rooted-cpanel-update.zip',
    'items_per_page'             => 20,
    'nearby_radius_km'           => 1.0,
    'google_calendar_enabled'    => false,
    'weather_enabled'            => false,
];
