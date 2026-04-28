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
    'version'                    => '3.0.17',
    'version_name'               => 'Inline-edit sown date on planning view',
    'update_zip_url'             => 'https://api.github.com/repos/maximebellefleur/villabelfiore/contents/rooted-cpanel-update.zip?ref=claude/create-rooted-project-RVbog',
    'update_version_url'         => 'https://api.github.com/repos/maximebellefleur/villabelfiore/contents/version.json?ref=claude/create-rooted-project-RVbog',
    'items_per_page'             => 20,
    'nearby_radius_km'           => 1.0,
    'google_calendar_enabled'    => false,
    'weather_enabled'            => false,
];
