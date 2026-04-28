<?php

namespace App\Support;

/**
 * Idempotent schema bootstrap for the Garden Redesign tables/columns.
 * Safe to call repeatedly — uses CREATE TABLE IF NOT EXISTS and SHOW COLUMNS guards.
 */
class GardenSchema
{
    public static function ensure(DB $db): void
    {
        $db->execute("CREATE TABLE IF NOT EXISTS garden_plantings (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            item_id INT UNSIGNED NOT NULL,
            line_number SMALLINT UNSIGNED NOT NULL DEFAULT 1,
            crop_name VARCHAR(200) DEFAULT NULL,
            variety VARCHAR(200) DEFAULT NULL,
            status ENUM('empty','planned','growing','harvested') NOT NULL DEFAULT 'empty',
            planted_at DATE DEFAULT NULL,
            sown_at DATE DEFAULT NULL,
            expected_harvest_at DATE DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            seed_id INT UNSIGNED DEFAULT NULL,
            plant_count SMALLINT UNSIGNED DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_garden_plantings_item (item_id, line_number)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->execute("CREATE TABLE IF NOT EXISTS garden_bed_lines (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            item_id INT UNSIGNED NOT NULL,
            line_number SMALLINT UNSIGNED NOT NULL,
            length_cm SMALLINT UNSIGNED DEFAULT NULL,
            sown_at DATE DEFAULT NULL,
            empty_since DATE DEFAULT NULL,
            last_watered_at DATETIME DEFAULT NULL,
            succession_crop_id INT UNSIGNED DEFAULT NULL,
            succession_starts_on DATE DEFAULT NULL,
            rotation_history JSON DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_garden_bed_lines (item_id, line_number)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->execute("CREATE TABLE IF NOT EXISTS seeds (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            variety VARCHAR(120) DEFAULT '',
            type ENUM('vegetable','herb','fruit','flower','other') NOT NULL DEFAULT 'vegetable',
            days_to_maturity SMALLINT UNSIGNED DEFAULT NULL,
            spacing_cm SMALLINT UNSIGNED DEFAULT NULL,
            companions JSON DEFAULT NULL,
            antagonists JSON DEFAULT NULL,
            stock_qty DECIMAL(12,3) NOT NULL DEFAULT 0,
            stock_unit ENUM('seeds','grams','packets') NOT NULL DEFAULT 'seeds',
            stock_low_threshold DECIMAL(12,3) DEFAULT NULL,
            stock_enabled TINYINT(1) NOT NULL DEFAULT 1,
            notes TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        self::ensureColumn($db, 'garden_plantings', 'sown_at',     "ALTER TABLE garden_plantings ADD COLUMN sown_at DATE DEFAULT NULL");

        self::ensureColumn($db, 'garden_bed_lines', 'sown_at',       "ALTER TABLE garden_bed_lines ADD COLUMN sown_at DATE DEFAULT NULL");
        self::ensureColumn($db, 'garden_bed_lines', 'empty_since',    "ALTER TABLE garden_bed_lines ADD COLUMN empty_since DATE DEFAULT NULL");
        self::ensureColumn($db, 'garden_bed_lines', 'last_watered_at',"ALTER TABLE garden_bed_lines ADD COLUMN last_watered_at DATETIME DEFAULT NULL");
        self::ensureColumn($db, 'garden_bed_lines', 'succession_crop_id',   "ALTER TABLE garden_bed_lines ADD COLUMN succession_crop_id INT UNSIGNED DEFAULT NULL");
        self::ensureColumn($db, 'garden_bed_lines', 'succession_starts_on', "ALTER TABLE garden_bed_lines ADD COLUMN succession_starts_on DATE DEFAULT NULL");
        self::ensureColumn($db, 'garden_bed_lines', 'rotation_history',     "ALTER TABLE garden_bed_lines ADD COLUMN rotation_history JSON DEFAULT NULL");

        self::ensureColumn($db, 'seeds', 'family',        "ALTER TABLE seeds ADD COLUMN family ENUM('root','leaf','fruit','herb','allium','legume','other') NOT NULL DEFAULT 'other'");
        self::ensureColumn($db, 'seeds', 'season',        "ALTER TABLE seeds ADD COLUMN season ENUM('cool','warm','any') NOT NULL DEFAULT 'any'");
        self::ensureColumn($db, 'seeds', 'emoji',         "ALTER TABLE seeds ADD COLUMN emoji VARCHAR(10) DEFAULT NULL");
        self::ensureColumn($db, 'seeds', 'color',         "ALTER TABLE seeds ADD COLUMN color CHAR(7) DEFAULT NULL");

    }

    private static function ensureColumn(DB $db, string $table, string $column, string $alter): void
    {
        try {
            $rows = $db->fetchAll("SHOW COLUMNS FROM {$table} LIKE ?", [$column]);
            if (empty($rows)) {
                $db->execute($alter);
            }
        } catch (\Throwable $e) {
            // table missing — ignore; CREATE TABLE earlier in ensure() should have created it
        }
    }
}
