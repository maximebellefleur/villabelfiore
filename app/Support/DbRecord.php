<?php

namespace App\Support;

/**
 * DbRecord — bulletproof INSERT / UPDATE / DELETE helpers.
 *
 * Builds fully-parameterised SQL from an associative array of column => value.
 * Every method catches all exceptions, logs them, and returns a safe result so
 * controllers never need their own try/catch for routine DB writes.
 *
 * Usage:
 *   $id = DbRecord::save($db, 'seeds', $fields);          // INSERT → new PK or 0
 *   $ok = DbRecord::update($db, 'seeds', $id, $fields);   // UPDATE by PK → bool
 *   $ok = DbRecord::delete($db, 'seeds', $id);            // DELETE by PK → bool
 */
class DbRecord
{
    /**
     * INSERT a row and return the new primary key (0 on failure).
     *
     * @param  DB                  $db
     * @param  string              $table   Literal table name — never user input.
     * @param  array<string,mixed> $fields  Column => value pairs.
     */
    public static function save(DB $db, string $table, array $fields): int
    {
        if (empty($fields)) return 0;
        try {
            $cols = implode(', ', array_keys($fields));
            $ph   = implode(', ', array_fill(0, count($fields), '?'));
            $db->execute("INSERT INTO {$table} ({$cols}) VALUES ({$ph})", array_values($fields));
            return (int) $db->lastInsertId();
        } catch (\Throwable $e) {
            Logger::error("DbRecord::save on {$table} failed — " . $e->getMessage());
            return 0;
        }
    }

    /**
     * UPDATE a row by its integer primary key. Returns true on success.
     *
     * @param  DB                  $db
     * @param  string              $table
     * @param  int                 $id     Primary key value.
     * @param  array<string,mixed> $fields Column => value pairs.
     */
    public static function update(DB $db, string $table, int $id, array $fields): bool
    {
        if (empty($fields) || $id <= 0) return false;
        try {
            $sets   = implode(', ', array_map(fn($k) => "{$k} = ?", array_keys($fields)));
            $values = array_values($fields);
            $values[] = $id;
            $db->execute("UPDATE {$table} SET {$sets} WHERE id = ?", $values);
            return true;
        } catch (\Throwable $e) {
            Logger::error("DbRecord::update on {$table}#{$id} failed — " . $e->getMessage());
            return false;
        }
    }

    /**
     * DELETE a row by its integer primary key. Returns true on success.
     */
    public static function delete(DB $db, string $table, int $id): bool
    {
        if ($id <= 0) return false;
        try {
            $db->execute("DELETE FROM {$table} WHERE id = ?", [$id]);
            return true;
        } catch (\Throwable $e) {
            Logger::error("DbRecord::delete on {$table}#{$id} failed — " . $e->getMessage());
            return false;
        }
    }

    /**
     * Soft-delete by setting a timestamp column to NOW().
     * Defaults to the standard deleted_at column.
     */
    public static function softDelete(DB $db, string $table, int $id, string $column = 'deleted_at'): bool
    {
        if ($id <= 0) return false;
        try {
            $db->execute("UPDATE {$table} SET {$column} = NOW() WHERE id = ?", [$id]);
            return true;
        } catch (\Throwable $e) {
            Logger::error("DbRecord::softDelete on {$table}#{$id} failed — " . $e->getMessage());
            return false;
        }
    }

    /**
     * Run any arbitrary write statement safely. Returns affected row count or -1 on failure.
     */
    public static function exec(DB $db, string $sql, array $params = []): int
    {
        try {
            return $db->execute($sql, $params);
        } catch (\Throwable $e) {
            Logger::error('DbRecord::exec failed — ' . $e->getMessage() . ' | SQL: ' . substr($sql, 0, 200));
            return -1;
        }
    }
}
