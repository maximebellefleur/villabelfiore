-- Rooted v1 — Full Database Schema
-- MySQL 8+ / MariaDB 10.5+
-- Run this during installation via InstallerService

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;

-- ============================================================
-- users
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email`           VARCHAR(190)    NOT NULL,
  `password_hash`   VARCHAR(255)    NOT NULL,
  `display_name`    VARCHAR(190)    NOT NULL,
  `is_active`       TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_login_at`   DATETIME            NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- items
-- ============================================================
CREATE TABLE IF NOT EXISTS `items` (
  `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid`              CHAR(36)        NOT NULL,
  `type`              VARCHAR(100)    NOT NULL,
  `subtype`           VARCHAR(100)        NULL DEFAULT NULL,
  `name`              VARCHAR(190)    NOT NULL,
  `slug`              VARCHAR(190)        NULL DEFAULT NULL,
  `parent_id`         BIGINT UNSIGNED     NULL DEFAULT NULL,
  `status`            ENUM('active','archived','trashed','draft') NOT NULL DEFAULT 'active',
  `gps_lat`           DECIMAL(10,7)       NULL DEFAULT NULL,
  `gps_lng`           DECIMAL(10,7)       NULL DEFAULT NULL,
  `gps_accuracy`      DECIMAL(8,2)        NULL DEFAULT NULL,
  `gps_source`        ENUM('device','manual','corrected') NOT NULL DEFAULT 'device',
  `is_finance_enabled` TINYINT(1)     NOT NULL DEFAULT 0,
  `is_mobile_asset`   TINYINT(1)      NOT NULL DEFAULT 0,
  `sort_order`        INT             NOT NULL DEFAULT 0,
  `created_by`        BIGINT UNSIGNED     NULL DEFAULT NULL,
  `created_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at`        DATETIME            NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_items_uuid` (`uuid`),
  KEY `idx_items_type`        (`type`),
  KEY `idx_items_parent_id`   (`parent_id`),
  KEY `idx_items_status`      (`status`),
  KEY `idx_items_type_status` (`type`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- item_meta
-- ============================================================
CREATE TABLE IF NOT EXISTS `item_meta` (
  `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `item_id`           BIGINT UNSIGNED NOT NULL,
  `meta_key`          VARCHAR(190)    NOT NULL,
  `meta_value_text`   LONGTEXT            NULL DEFAULT NULL,
  `meta_value_json`   JSON                NULL DEFAULT NULL,
  `value_type`        ENUM('text','number','boolean','date','json') NOT NULL DEFAULT 'text',
  `created_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_item_meta_key` (`item_id`, `meta_key`),
  KEY `idx_item_meta_item_id` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- item_relationships
-- ============================================================
CREATE TABLE IF NOT EXISTS `item_relationships` (
  `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `from_item_id`      BIGINT UNSIGNED NOT NULL,
  `to_item_id`        BIGINT UNSIGNED NOT NULL,
  `relationship_type` VARCHAR(100)    NOT NULL,
  `created_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rel_from` (`from_item_id`),
  KEY `idx_rel_to`   (`to_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- attachments
-- ============================================================
CREATE TABLE IF NOT EXISTS `attachments` (
  `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid`              CHAR(36)        NOT NULL,
  `item_id`           BIGINT UNSIGNED NOT NULL,
  `category`          VARCHAR(100)    NOT NULL DEFAULT 'general_attachment',
  `original_filename` VARCHAR(255)    NOT NULL,
  `stored_filename`   VARCHAR(255)    NOT NULL,
  `mime_type`         VARCHAR(100)    NOT NULL,
  `extension`         VARCHAR(20)         NULL DEFAULT NULL,
  `storage_driver`    VARCHAR(50)     NOT NULL DEFAULT 'local',
  `storage_path`      VARCHAR(500)    NOT NULL,
  `file_size_bytes`   BIGINT UNSIGNED     NULL DEFAULT NULL,
  `checksum_sha256`   CHAR(64)            NULL DEFAULT NULL,
  `caption`           VARCHAR(500)        NULL DEFAULT NULL,
  `is_primary`        TINYINT(1)      NOT NULL DEFAULT 0,
  `captured_at`       DATETIME            NULL DEFAULT NULL,
  `uploaded_at`       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `uploaded_by`       BIGINT UNSIGNED     NULL DEFAULT NULL,
  `status`            ENUM('active','archived','trashed') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_attachments_uuid` (`uuid`),
  KEY `idx_attachments_item_id` (`item_id`),
  KEY `idx_attachments_status`  (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- activity_log  (append-only, successful actions only)
-- ============================================================
CREATE TABLE IF NOT EXISTS `activity_log` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `item_id`       BIGINT UNSIGNED     NULL DEFAULT NULL,
  `item_type`     VARCHAR(100)        NULL DEFAULT NULL,
  `action_type`   VARCHAR(100)    NOT NULL,
  `action_label`  VARCHAR(190)    NOT NULL,
  `description`   TEXT            NOT NULL,
  `payload_json`  JSON                NULL DEFAULT NULL,
  `gps_lat`       DECIMAL(10,7)       NULL DEFAULT NULL,
  `gps_lng`       DECIMAL(10,7)       NULL DEFAULT NULL,
  `performed_by`  BIGINT UNSIGNED     NULL DEFAULT NULL,
  `performed_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_activity_item_id`   (`item_id`),
  KEY `idx_activity_action`    (`action_type`),
  KEY `idx_activity_performed` (`performed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- error_logs
-- ============================================================
CREATE TABLE IF NOT EXISTS `error_logs` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `severity`      ENUM('info','warning','error','critical') NOT NULL DEFAULT 'error',
  `module`        VARCHAR(100)    NOT NULL DEFAULT 'app',
  `context_key`   VARCHAR(190)        NULL DEFAULT NULL,
  `item_id`       BIGINT UNSIGNED     NULL DEFAULT NULL,
  `message`       VARCHAR(255)    NOT NULL,
  `details`       LONGTEXT            NULL DEFAULT NULL,
  `trace_text`    LONGTEXT            NULL DEFAULT NULL,
  `request_json`  JSON                NULL DEFAULT NULL,
  `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `resolved_at`   DATETIME            NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_error_severity`   (`severity`),
  KEY `idx_error_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- action_types
-- ============================================================
CREATE TABLE IF NOT EXISTS `action_types` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `scope_type`    VARCHAR(100)        NULL DEFAULT NULL,
  `action_key`    VARCHAR(100)    NOT NULL,
  `action_label`  VARCHAR(190)    NOT NULL,
  `is_system`     TINYINT(1)      NOT NULL DEFAULT 0,
  `is_active`     TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_action_key` (`action_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- reminders
-- ============================================================
CREATE TABLE IF NOT EXISTS `reminders` (
  `id`                      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `item_id`                 BIGINT UNSIGNED     NULL DEFAULT NULL,
  `type`                    VARCHAR(100)    NOT NULL DEFAULT 'general',
  `title`                   VARCHAR(190)    NOT NULL,
  `description`             TEXT                NULL DEFAULT NULL,
  `due_at`                  DATETIME        NOT NULL,
  `is_recurring`            TINYINT(1)      NOT NULL DEFAULT 0,
  `recurrence_rule_json`    JSON                NULL DEFAULT NULL,
  `source_type`             VARCHAR(100)        NULL DEFAULT NULL,
  `status`                  ENUM('pending','completed','dismissed','archived') NOT NULL DEFAULT 'pending',
  `google_calendar_event_id` VARCHAR(190)       NULL DEFAULT NULL,
  `created_at`              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reminders_item_id` (`item_id`),
  KEY `idx_reminders_status`  (`status`),
  KEY `idx_reminders_due_at`  (`due_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- harvest_entries
-- ============================================================
CREATE TABLE IF NOT EXISTS `harvest_entries` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `item_id`       BIGINT UNSIGNED NOT NULL,
  `harvest_type`  VARCHAR(100)    NOT NULL DEFAULT 'general',
  `quantity`      DECIMAL(12,3)   NOT NULL,
  `unit`          VARCHAR(50)     NOT NULL,
  `quality_grade` VARCHAR(100)        NULL DEFAULT NULL,
  `notes`         TEXT                NULL DEFAULT NULL,
  `recorded_at`   DATETIME        NOT NULL,
  `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_harvest_item_id`     (`item_id`),
  KEY `idx_harvest_recorded_at` (`recorded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- finance_entries
-- ============================================================
CREATE TABLE IF NOT EXISTS `finance_entries` (
  `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `item_id`             BIGINT UNSIGNED     NULL DEFAULT NULL,
  `related_harvest_id`  BIGINT UNSIGNED     NULL DEFAULT NULL,
  `entry_type`          ENUM('cost','revenue','market_reference') NOT NULL,
  `category`            VARCHAR(100)    NOT NULL,
  `label`               VARCHAR(190)    NOT NULL,
  `amount`              DECIMAL(12,2)   NOT NULL,
  `currency`            CHAR(3)         NOT NULL DEFAULT 'EUR',
  `quantity`            DECIMAL(12,3)       NULL DEFAULT NULL,
  `unit`                VARCHAR(50)         NULL DEFAULT NULL,
  `notes`               TEXT                NULL DEFAULT NULL,
  `entry_date`          DATE            NOT NULL,
  `created_at`          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_finance_item_id`    (`item_id`),
  KEY `idx_finance_entry_date` (`entry_date`),
  KEY `idx_finance_type`       (`entry_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- drafts
-- ============================================================
CREATE TABLE IF NOT EXISTS `drafts` (
  `id`                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `draft_key`             CHAR(36)        NOT NULL,
  `draft_type`            VARCHAR(100)    NOT NULL,
  `target_item_id`        BIGINT UNSIGNED     NULL DEFAULT NULL,
  `source_device_type`    VARCHAR(50)         NULL DEFAULT NULL,
  `payload_json`          JSON            NOT NULL,
  `validation_errors_json` JSON               NULL DEFAULT NULL,
  `status`                ENUM('active','resolved','discarded') NOT NULL DEFAULT 'active',
  `created_at`            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_drafts_key` (`draft_key`),
  KEY `idx_drafts_type`   (`draft_type`),
  KEY `idx_drafts_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- sync_queue
-- ============================================================
CREATE TABLE IF NOT EXISTS `sync_queue` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `entity_type`     VARCHAR(100)    NOT NULL,
  `entity_id`       BIGINT UNSIGNED     NULL DEFAULT NULL,
  `operation_type`  ENUM('create','update','delete','restore') NOT NULL,
  `payload_json`    JSON            NOT NULL,
  `conflict_state`  ENUM('none','pending_review','resolved') NOT NULL DEFAULT 'none',
  `sync_status`     ENUM('queued','processing','synced','failed') NOT NULL DEFAULT 'queued',
  `error_message`   TEXT                NULL DEFAULT NULL,
  `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sync_status`      (`sync_status`),
  KEY `idx_sync_entity_type` (`entity_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- settings
-- ============================================================
CREATE TABLE IF NOT EXISTS `settings` (
  `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key`         VARCHAR(190)    NOT NULL,
  `setting_value_json`  JSON                NULL DEFAULT NULL,
  `setting_value_text`  LONGTEXT            NULL DEFAULT NULL,
  `value_type`          ENUM('text','number','boolean','json') NOT NULL DEFAULT 'json',
  `autoload`            TINYINT(1)      NOT NULL DEFAULT 0,
  `updated_at`          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_settings_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- storage_targets
-- ============================================================
CREATE TABLE IF NOT EXISTS `storage_targets` (
  `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`                VARCHAR(190)    NOT NULL,
  `driver`              VARCHAR(50)     NOT NULL DEFAULT 'local',
  `config_json`         JSON            NOT NULL,
  `is_default_live`     TINYINT(1)      NOT NULL DEFAULT 0,
  `is_default_backup`   TINYINT(1)      NOT NULL DEFAULT 0,
  `is_active`           TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at`          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- item_location_history
-- ============================================================
CREATE TABLE IF NOT EXISTS `item_location_history` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `item_id`      BIGINT UNSIGNED NOT NULL,
  `gps_lat`      DECIMAL(10,7)   NOT NULL,
  `gps_lng`      DECIMAL(10,7)   NOT NULL,
  `gps_accuracy` DECIMAL(8,2)        NULL DEFAULT NULL,
  `recorded_at`  DATETIME        NOT NULL,
  `source`       VARCHAR(50)     NOT NULL DEFAULT 'device',
  PRIMARY KEY (`id`),
  KEY `idx_location_item_id`    (`item_id`),
  KEY `idx_location_recorded_at`(`recorded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;

-- ============================================================
-- Seeds — action_types
-- ============================================================
INSERT IGNORE INTO `action_types` (`action_key`, `action_label`, `scope_type`, `is_system`, `is_active`) VALUES
('pruning',          'Pruning',              'tree',       1, 1),
('treatment',        'Treatment',            'tree',       1, 1),
('amendment',        'Amendment',            NULL,         1, 1),
('harvest',          'Harvest',              NULL,         1, 1),
('image_refresh',    'Image Refresh',        'tree',       1, 1),
('note',             'Note',                 NULL,         1, 1),
('planting',         'Planting',             'garden',     1, 1),
('move',             'Move',                 NULL,         1, 1),
('maintenance',      'Maintenance',          NULL,         1, 1),
('conversion',       'Conversion',           'prep_zone',  1, 1),
('item_created',     'Item Created',         NULL,         1, 1),
('item_updated',     'Item Updated',         NULL,         1, 1),
('item_archived',    'Item Archived',        NULL,         1, 1),
('item_restored',    'Item Restored',        NULL,         1, 1),
('image_uploaded',   'Image Uploaded',       NULL,         1, 1);

-- ============================================================
-- Seeds — default settings
-- ============================================================
INSERT IGNORE INTO `settings` (`setting_key`, `setting_value_text`, `value_type`, `autoload`) VALUES
('app.name',              'Rooted',        'text', 1),
('app.currency',          'EUR',           'text', 1),
('app.language',          'en',            'text', 1),
('app.timezone',          'Europe/Rome',   'text', 1),
('app.version',           '1.0.0',         'text', 0),
('app.installed',         '0',             'number', 1),
('storage.driver',        'local',         'text', 1),
('backup.mode',           'manual',        'text', 0),
('gps.accuracy_threshold', '20',           'number', 0),
('image.refresh_interval_days', '365',     'number', 0),
('reminder.default_lead_days',  '7',       'number', 0),
('integration.google_calendar', '0',       'boolean', 0),
('integration.weather',         '0',       'boolean', 0);
