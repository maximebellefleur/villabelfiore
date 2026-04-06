<?php

namespace App\Support;

/**
 * Loads and merges per-type harvest configuration.
 * Source of truth: settings table key 'harvest.type_config' (JSON).
 * Falls back to defaults derived from config/item_types.php.
 */
class HarvestConfig
{
    /** Built-in slider/unit defaults per type (overridable via settings). */
    private static array $sliderDefaults = [
        'olive_tree'  => ['unit' => 'baskets',      'slider_max' => 2.0,  'slider_step' => 0.25],
        'almond_tree' => ['unit' => 'wheelbarrows', 'slider_max' => 5.0,  'slider_step' => 0.25],
        'vine'        => ['unit' => 'kg',            'slider_max' => 50.0, 'slider_step' => 1.0],
        'tree'        => ['unit' => 'kg',            'slider_max' => 20.0, 'slider_step' => 1.0],
    ];

    /**
     * Returns the effective harvest config for every item type.
     * Shape: [ 'olive_tree' => ['enabled'=>1,'max_per_year'=>1,'unit'=>'baskets','slider_max'=>2.0,'slider_step'=>0.25], ... ]
     */
    public static function get(): array
    {
        $itemTypesConfig = require BASE_PATH . '/config/item_types.php';

        // Load saved overrides from DB
        $row   = DB::getInstance()->fetchOne(
            "SELECT setting_value_json FROM settings WHERE setting_key = 'harvest.type_config' LIMIT 1"
        );
        $saved = ($row && !empty($row['setting_value_json']))
            ? (json_decode($row['setting_value_json'], true) ?: [])
            : [];

        $config = [];
        foreach ($itemTypesConfig as $typeKey => $typeCfg) {
            $defaults = [
                'enabled'      => !empty($typeCfg['harvest_enabled']) ? 1 : 0,
                'max_per_year' => (int) ($typeCfg['harvest_max_per_year'] ?? 1),
                'unit'         => self::$sliderDefaults[$typeKey]['unit']        ?? 'units',
                'slider_max'   => self::$sliderDefaults[$typeKey]['slider_max']  ?? 5.0,
                'slider_step'  => self::$sliderDefaults[$typeKey]['slider_step'] ?? 0.25,
            ];
            $config[$typeKey] = isset($saved[$typeKey])
                ? array_merge($defaults, array_intersect_key($saved[$typeKey], $defaults))
                : $defaults;
        }

        return $config;
    }

    /** Returns just the default slider/unit values (used when building the settings form). */
    public static function sliderDefaults(): array
    {
        return self::$sliderDefaults;
    }
}
