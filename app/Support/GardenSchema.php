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

        self::ensureColumn($db, 'garden_plantings', 'sown_at',              "ALTER TABLE garden_plantings ADD COLUMN sown_at DATE DEFAULT NULL");
        self::ensureColumn($db, 'garden_plantings', 'planted_at',           "ALTER TABLE garden_plantings ADD COLUMN planted_at DATE DEFAULT NULL");
        self::ensureColumn($db, 'garden_plantings', 'sort_order',            "ALTER TABLE garden_plantings ADD COLUMN sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0");
        self::ensureColumn($db, 'garden_plantings', 'seed_id',               "ALTER TABLE garden_plantings ADD COLUMN seed_id INT UNSIGNED DEFAULT NULL");
        self::ensureColumn($db, 'garden_plantings', 'plant_count',           "ALTER TABLE garden_plantings ADD COLUMN plant_count SMALLINT UNSIGNED DEFAULT NULL");
        self::ensureColumn($db, 'garden_plantings', 'expected_harvest_at',   "ALTER TABLE garden_plantings ADD COLUMN expected_harvest_at DATE DEFAULT NULL");
        self::ensureColumn($db, 'garden_plantings', 'updated_at',            "ALTER TABLE garden_plantings ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");

        self::ensureColumn($db, 'garden_bed_lines', 'sown_at',       "ALTER TABLE garden_bed_lines ADD COLUMN sown_at DATE DEFAULT NULL");
        self::ensureColumn($db, 'garden_bed_lines', 'empty_since',    "ALTER TABLE garden_bed_lines ADD COLUMN empty_since DATE DEFAULT NULL");
        self::ensureColumn($db, 'garden_bed_lines', 'last_watered_at',"ALTER TABLE garden_bed_lines ADD COLUMN last_watered_at DATETIME DEFAULT NULL");
        self::ensureColumn($db, 'garden_bed_lines', 'succession_crop_id',   "ALTER TABLE garden_bed_lines ADD COLUMN succession_crop_id INT UNSIGNED DEFAULT NULL");
        self::ensureColumn($db, 'garden_bed_lines', 'succession_starts_on', "ALTER TABLE garden_bed_lines ADD COLUMN succession_starts_on DATE DEFAULT NULL");
        self::ensureColumn($db, 'garden_bed_lines', 'rotation_history',     "ALTER TABLE garden_bed_lines ADD COLUMN rotation_history JSON DEFAULT NULL");

        self::ensureColumn($db, 'seeds', 'family',        "ALTER TABLE seeds ADD COLUMN family ENUM('root','leaf','fruit','herb','allium','legume','other') NOT NULL DEFAULT 'other'");
        self::ensureColumn($db, 'seeds', 'season',        "ALTER TABLE seeds ADD COLUMN season ENUM('cool','warm','any') NOT NULL DEFAULT 'any'");
        self::ensureColumn($db, 'seeds', 'emoji',          "ALTER TABLE seeds ADD COLUMN emoji VARCHAR(10) DEFAULT NULL");
        self::ensureColumn($db, 'seeds', 'color',           "ALTER TABLE seeds ADD COLUMN color CHAR(7) DEFAULT NULL");
        self::ensureColumn($db, 'seeds', 'harvest_months', "ALTER TABLE seeds ADD COLUMN harvest_months JSON DEFAULT NULL");
        self::ensureColumn($db, 'seeds', 'gardener_note',  "ALTER TABLE seeds ADD COLUMN gardener_note TEXT DEFAULT NULL");

        // ── New columns for harvest projection system (v3.1.74) ─────────
        // projected_seed_prod_count = currently growing/sown + planned-with-past-date,
        //   recomputed by daily cron and on every bed write.
        // harvested_seed_prod_count = lifetime harvested plant_count (incremented on harvest).
        self::ensureColumn($db, 'seeds', 'harvested_seed_prod_count', "ALTER TABLE seeds ADD COLUMN harvested_seed_prod_count INT NOT NULL DEFAULT 0");
        self::ensureColumn($db, 'seeds', 'projected_seed_prod_count', "ALTER TABLE seeds ADD COLUMN projected_seed_prod_count INT NOT NULL DEFAULT 0");

        // Drop the old seed_ground_cache (replaced by the two columns above).
        try { $db->execute("DROP TABLE IF EXISTS seed_ground_cache"); } catch (\Throwable $e) {}

        // One-time backfill — guarded by a settings flag.
        try {
            $row = $db->fetchOne("SELECT setting_key FROM settings WHERE setting_key = 'seeds.prod_counts.backfilled' LIMIT 1");
            if (!$row) {
                self::backfillSeedProdCounts($db);
                $db->execute(
                    "INSERT INTO settings (setting_key, setting_value_text, value_type, autoload, updated_at)
                     VALUES ('seeds.prod_counts.backfilled', '1', 'text', 0, NOW())
                     ON DUPLICATE KEY UPDATE setting_value_text = '1', updated_at = NOW()"
                );
            }
        } catch (\Throwable $e) {}

        try {
            $db->execute("CREATE TABLE IF NOT EXISTS seed_harvest_log (
                id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                seed_id      BIGINT UNSIGNED NOT NULL,
                bed_id       BIGINT UNSIGNED NOT NULL,
                plant_count  INT NOT NULL DEFAULT 1,
                harvested_on DATE NOT NULL,
                created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_shl_seed_year (seed_id, harvested_on)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (\Throwable $e) {}

        self::migrateBedRows($db);
        self::fixNullPlantCounts($db);
        self::backfillSeedColors($db);
    }

    private static function fixNullPlantCounts(DB $db): void
    {
        // Set plant_count = 1 for any active planting row that has NULL or 0 count.
        // This is a data fix — existing rows entered before plant_count was tracked
        // need a minimum of 1 so seedGroundStats shows a count instead of 0.
        try {
            $cols = $db->fetchAll("SHOW COLUMNS FROM garden_plantings LIKE 'plant_count'");
            if (empty($cols)) return;
            $db->execute(
                "UPDATE garden_plantings SET plant_count = 1
                 WHERE (plant_count IS NULL OR plant_count = 0)
                   AND status IN ('growing','planned','sown')"
            );
        } catch (\Throwable $e) {}
    }

    private static function migrateBedRows(DB $db): void
    {
        try {
            $tables = $db->fetchAll("SHOW TABLES LIKE 'bed_rows'");
            if (empty($tables)) return;

            $db->execute("
                INSERT INTO garden_plantings
                    (item_id, line_number, seed_id, crop_name, plant_count, sown_at, planted_at, status, created_at, updated_at)
                SELECT
                    br.item_id,
                    br.row_number,
                    br.seed_id,
                    s.name,
                    br.plant_count,
                    br.sowing_date,
                    br.sowing_date,
                    CASE br.status
                        WHEN 'sown'      THEN 'growing'
                        WHEN 'growing'   THEN 'growing'
                        WHEN 'harvested' THEN 'harvested'
                        ELSE 'planned'
                    END,
                    br.created_at,
                    br.updated_at
                FROM bed_rows br
                LEFT JOIN seeds s ON s.id = br.seed_id
                WHERE NOT EXISTS (
                    SELECT 1 FROM garden_plantings gp
                    WHERE gp.item_id = br.item_id
                      AND gp.line_number = br.row_number
                      AND gp.seed_id = br.seed_id
                )
            ");

            $db->execute("DROP TABLE IF EXISTS bed_rows");
        } catch (\Throwable $e) {
            // table missing or already dropped
        }
    }

    /**
     * One-time migration: populate harvested_seed_prod_count from seed_harvest_log
     * and projected_seed_prod_count from current garden_plantings. Idempotent for
     * a single run — guarded by settings flag in ensure().
     */
    private static function backfillSeedProdCounts(DB $db): void
    {
        try {
            // Harvested totals from seed_harvest_log
            $rows = $db->fetchAll(
                "SELECT seed_id, SUM(plant_count) AS total
                 FROM seed_harvest_log
                 GROUP BY seed_id"
            );
            foreach ($rows as $r) {
                $db->execute(
                    "UPDATE seeds SET harvested_seed_prod_count = ? WHERE id = ?",
                    [(int)$r['total'], (int)$r['seed_id']]
                );
            }
        } catch (\Throwable $e) {}

        // Projected totals from current plantings — handled by GardenHelpers.
        try {
            $seedIds = array_column($db->fetchAll("SELECT id FROM seeds"), 'id');
            if (!empty($seedIds)) {
                \App\Support\GardenHelpers::recalcSeedsProjected($db, $seedIds);
            }
        } catch (\Throwable $e) {}
    }

    private static function backfillSeedColors(DB $db): void
    {
        // Seeds created before the color column was introduced have color = NULL.
        // Backfill them with the same computed default used by the edit form so the
        // catalog list and the edit form always show the same color.
        try {
            $seeds = $db->fetchAll("SELECT id, name FROM seeds WHERE color IS NULL OR color = ''");
            foreach ($seeds as $s) {
                $color = GardenHelpers::defaultCatalogColor((int)$s['id']);
                $db->execute("UPDATE seeds SET color = ? WHERE id = ?", [$color, (int)$s['id']]);
            }
        } catch (\Throwable $e) {}
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
