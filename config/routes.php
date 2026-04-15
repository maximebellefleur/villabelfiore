<?php

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\ItemController;
use App\Controllers\AttachmentController;
use App\Controllers\ReminderController;
use App\Controllers\HarvestController;
use App\Controllers\FinanceController;
use App\Controllers\SettingsController;
use App\Controllers\ErrorLogController;
use App\Controllers\SyncController;
use App\Controllers\InstallerController;
use App\Controllers\MapController;
use App\Controllers\UpgradeController;
use App\Controllers\CalendarController;
use App\Controllers\PublicController;
use App\Controllers\PwaController;
use App\Controllers\SeedController;
use App\Controllers\AiController;

/** @var \App\Support\Router $router */

// -------------------------------------------------------------------------
// Public (no auth required)
// -------------------------------------------------------------------------
$router->get('/',        'PublicController@home');
$router->get('/privacy', 'PublicController@privacy');

// -------------------------------------------------------------------------
// Installer (redirect if already installed)
// -------------------------------------------------------------------------

// -------------------------------------------------------------------------
// Installer
// -------------------------------------------------------------------------
$router->get('/install',           'InstallerController@index');
$router->post('/install/step/1',   'InstallerController@step1');
$router->post('/install/step/2',   'InstallerController@step2');
$router->post('/install/step/3',   'InstallerController@step3');
$router->post('/install/step/4',   'InstallerController@step4');
$router->post('/install/step/5',   'InstallerController@step5');
$router->post('/install/finish',   'InstallerController@finish');

// -------------------------------------------------------------------------
// Auth
// -------------------------------------------------------------------------
$router->get('/login',  'AuthController@showLogin');
$router->post('/login', 'AuthController@login');
$router->post('/logout','AuthController@logout');

// -------------------------------------------------------------------------
// Dashboard  (auth required — enforced inside controllers)
// -------------------------------------------------------------------------
$router->get('/dashboard',          'DashboardController@index');
$router->get('/dashboard/overview', 'DashboardController@overview');
$router->get('/dashboard/map',      'DashboardController@map');
$router->get('/dashboard/nearby',   'DashboardController@nearby');
$router->get('/dashboard/reports',  'DashboardController@reports');

// -------------------------------------------------------------------------
// Items
// -------------------------------------------------------------------------
$router->get('/items',                    'ItemController@index');
$router->get('/items/create',             'ItemController@create');
$router->post('/items',                   'ItemController@store');
$router->get('/items/{id}',               'ItemController@show');
$router->get('/items/{id}/edit',          'ItemController@edit');
$router->post('/items/{id}/update',       'ItemController@update');
$router->post('/items/{id}/trash',        'ItemController@trash');
$router->post('/items/{id}/restore',      'ItemController@restore');
$router->post('/items/{id}/archive',      'ItemController@archive');
$router->get('/items/{id}/photos',        'ItemController@photos');
$router->get('/items/{id}/ai-prompt',     'ItemController@aiPrompt');
$router->get('/items/{id}/actions',       'ItemController@actions');
$router->post('/items/{id}/actions',      'ItemController@addAction');
$router->post('/activity-log/{id}/trash', 'ItemController@deleteLog');
$router->post('/settings/tree-types/add',  'SettingsController@addTreeType');

// -------------------------------------------------------------------------
// Attachments
// -------------------------------------------------------------------------
$router->get('/items/{id}/attachments',        'AttachmentController@index');
$router->post('/items/{id}/attachments',       'AttachmentController@store');
$router->post('/attachments/{id}/trash',       'AttachmentController@trash');
$router->post('/attachments/{id}/restore',     'AttachmentController@restore');
$router->post('/attachments/{id}/category',    'AttachmentController@updateCategory');
$router->post('/attachments/{id}/caption',     'AttachmentController@updateCaption');
$router->get('/attachments/{id}/download',     'AttachmentController@download');

// -------------------------------------------------------------------------
// Reminders
// -------------------------------------------------------------------------
$router->get('/reminders',                     'ReminderController@index');
$router->post('/reminders',                    'ReminderController@store');
$router->post('/reminders/{id}/complete',      'ReminderController@complete');
$router->post('/reminders/{id}/dismiss',       'ReminderController@dismiss');

// -------------------------------------------------------------------------
// Harvests
// -------------------------------------------------------------------------
$router->get('/harvest/quick',                 'HarvestController@quickEntry');
$router->get('/photos/quick',                  'AttachmentController@quickPhotos');
$router->get('/items/{id}/harvests',           'HarvestController@index');
$router->post('/items/{id}/harvests',          'HarvestController@store');
$router->post('/harvests/{id}/update',         'HarvestController@update');
$router->post('/harvests/{id}/trash',          'HarvestController@trash');

// -------------------------------------------------------------------------
// Finance
// -------------------------------------------------------------------------
$router->get('/finance',                       'FinanceController@index');
$router->get('/items/{id}/finance',            'FinanceController@forItem');
$router->post('/finance',                      'FinanceController@store');
$router->post('/finance/{id}/update',          'FinanceController@update');
$router->post('/finance/{id}/trash',           'FinanceController@trash');

// -------------------------------------------------------------------------
// Settings
// -------------------------------------------------------------------------
$router->get('/settings',                      'SettingsController@index');
$router->post('/settings/update',              'SettingsController@update');
$router->get('/settings/storage',              'SettingsController@storage');
$router->post('/settings/storage',             'SettingsController@updateStorage');
$router->get('/settings/harvest',              'SettingsController@harvest');
$router->post('/settings/harvest',             'SettingsController@updateHarvest');
$router->get('/settings/action-types',         'SettingsController@actionTypes');
$router->post('/settings/action-types',        'SettingsController@updateActionTypes');
$router->get('/settings/weather',              'SettingsController@weather');
$router->post('/settings/weather',             'SettingsController@updateWeather');
$router->get('/settings/upgrade',              'UpgradeController@index');
$router->post('/settings/upgrade/upload',      'UpgradeController@upload');
$router->post('/settings/upgrade/github',      'UpgradeController@applyFromGitHub');
$router->get('/settings/upcoming',             'SettingsController@upcoming');
$router->get('/settings/pwa',                  'PwaController@pwa');
$router->post('/settings/pwa',                 'PwaController@updatePwa');
$router->post('/settings/pwa/upload-icon',     'PwaController@uploadIcon');
$router->get('/manifest.json',                 'PwaController@serveManifest');
$router->get('/offline',                       'PublicController@offline');
$router->post('/settings/map',                 'SettingsController@updateMap');
$router->post('/settings/logo',                'SettingsController@uploadLogo');
$router->post('/settings/logo/delete',         'SettingsController@deleteLogo');
$router->get('/settings/calendar',             'CalendarController@index');
$router->post('/settings/calendar/save',       'CalendarController@save');
$router->get('/settings/calendar/connect',     'CalendarController@connect');
$router->get('/settings/calendar/callback',    'CalendarController@callback');
$router->post('/settings/calendar/disconnect', 'CalendarController@disconnect');
$router->post('/settings/calendar/sync',       'CalendarController@sync');

// -------------------------------------------------------------------------
// Seeds
// -------------------------------------------------------------------------
$router->get('/seeds',                    'SeedController@index');
$router->get('/seeds/create',             'SeedController@create');
$router->get('/seeds/family-needs',       'SeedController@familyNeeds');
$router->post('/seeds/family-needs',      'SeedController@storeFamilyNeed');
$router->post('/seeds',                   'SeedController@store');
$router->get('/seeds/{id}',               'SeedController@show');
$router->get('/seeds/{id}/edit',          'SeedController@edit');
$router->post('/seeds/{id}/update',       'SeedController@update');
$router->post('/seeds/{id}/trash',        'SeedController@trash');
$router->post('/seeds/{id}/stock',        'SeedController@adjustStock');
$router->get('/items/{id}/rows',          'SeedController@bedRows');
$router->post('/items/{id}/rows',         'SeedController@storeBedRow');
$router->post('/bed-rows/{id}/update',    'SeedController@updateBedRow');
$router->post('/bed-rows/{id}/trash',     'SeedController@trashBedRow');
$router->post('/family-needs/{id}/update','SeedController@updateFamilyNeed');
$router->post('/family-needs/{id}/trash', 'SeedController@trashFamilyNeed');

// -------------------------------------------------------------------------
// Logs
// -------------------------------------------------------------------------
$router->get('/logs/errors',                   'ErrorLogController@index');
$router->get('/logs/errors/{id}',              'ErrorLogController@show');
$router->get('/activity-log',                  'ErrorLogController@activity');

// -------------------------------------------------------------------------
// Sync
// -------------------------------------------------------------------------
$router->get('/sync/status',                   'SyncController@status');
$router->post('/sync/push',                    'SyncController@push');
$router->get('/sync/conflicts',                'SyncController@conflicts');
$router->post('/sync/conflicts/{id}/resolve',  'SyncController@resolve');

// -------------------------------------------------------------------------
// JSON API  (/api/*)
// -------------------------------------------------------------------------
$router->get('/api/map/items',                    'MapController@apiItems');
$router->post('/api/map/land-boundary',           'MapController@saveLandBoundary');
$router->post('/api/map/land-boundary/delete',    'MapController@deleteLandBoundary');
$router->post('/api/map/boundary/{id}',           'MapController@saveBoundary');
$router->post('/api/map/boundary/{id}/delete',    'MapController@deleteBoundary');

$router->get('/api/items/nearby',              'ItemController@apiNearby');
$router->get('/api/items/{id}',                'ItemController@apiShow');
$router->post('/api/items',                    'ItemController@apiStore');
$router->post('/api/items/{id}/actions',       'ItemController@apiAddAction');
$router->post('/api/ai/identify-seed',         'AiController@identifySeed');
$router->post('/api/items/{id}/harvests',      'HarvestController@apiStore');
$router->get('/api/reminders',                 'ReminderController@apiIndex');
$router->post('/api/sync/push',                'SyncController@apiPush');
$router->get('/api/dashboard/summary',         'DashboardController@apiSummary');
