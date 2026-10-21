<?php

namespace VersionPress\Database;

use VersionPress\DI\VersionPressServices;

/**
 * The `wpdb` class WordPress uses while VersionPress is active. It mirrors every successful
 * data change into VersionPress storages so that it ends up in the Git repository.
 *
 * It is installed by the `wp-content/db.php` drop-in (see {@see \VersionPress\Initialization\DbDropIn})
 * which WordPress loads right after its own `wpdb` and before any plugin.
 *
 * VersionPress's own queries must not be tracked; they go through {@see Database}, which calls
 * {@see VersionPressWpdb::queryWithoutTracking()}.
 */
class VersionPressWpdb extends \wpdb
{
    /**
     * Set while `insert()`, `update()` and `delete()` run: they call `query()` internally and the change
     * is already tracked by them.
     *
     * @var bool
     */
    private $insideTrackedMethod = false;

    public function insert($table, $data, $format = null)
    {
        $wpdbMirrorBridge = $this->getMirrorBridge();
        if ($wpdbMirrorBridge === null) {
            return parent::insert($table, $data, $format);
        }

        $this->insideTrackedMethod = true;
        try {
            $result = parent::insert($table, $data, $format);
            if ($result !== false) {
                $this->runPreservingFields(fn() => $wpdbMirrorBridge->insert($table, $data));
            }
            return $result;
        } finally {
            $this->insideTrackedMethod = false;
        }
    }

    public function update($table, $data, $where, $format = null, $where_format = null)
    {
        $wpdbMirrorBridge = $this->getMirrorBridge();
        if ($wpdbMirrorBridge === null) {
            return parent::update($table, $data, $where, $format, $where_format);
        }

        $this->insideTrackedMethod = true;
        try {
            $result = parent::update($table, $data, $where, $format, $where_format);
            if ($result !== false) {
                $this->runPreservingFields(fn() => $wpdbMirrorBridge->update($table, $data, $where));
            }
            return $result;
        } finally {
            $this->insideTrackedMethod = false;
        }
    }

    public function delete($table, $where, $where_format = null)
    {
        $wpdbMirrorBridge = $this->getMirrorBridge();
        if ($wpdbMirrorBridge === null) {
            return parent::delete($table, $where, $where_format);
        }

        $this->insideTrackedMethod = true;
        try {
            // Parent IDs are only available before the row is gone.
            $parentIds = $wpdbMirrorBridge->getParentIdsBeforeDelete($table, $where);

            $result = parent::delete($table, $where, $where_format);
            if ($result !== false) {
                $this->runPreservingFields(fn() => $wpdbMirrorBridge->delete($table, $where, $parentIds));
            }
            return $result;
        } finally {
            $this->insideTrackedMethod = false;
        }
    }

    public function query($query)
    {
        global $versionPressContainer;

        if ($versionPressContainer === null || $this->insideTrackedMethod) {
            return parent::query($query);
        }

        $normalizedQuery = strtolower(trim($query));
        if (str_starts_with($normalizedQuery, 'select') || str_starts_with($normalizedQuery, 'show')) {
            // Read-only queries don't change anything VersionPress tracks.
            return parent::query($query);
        }

        /** @var SqlQueryParser $sqlQueryParser */
        $sqlQueryParser = $versionPressContainer->resolve(VersionPressServices::SQL_QUERY_PARSER);
        $parsedQueryData = $sqlQueryParser->parseQuery($query);

        if ($parsedQueryData === null) {
            return parent::query($query);
        }

        $isDelete = $parsedQueryData->queryType === ParsedQueryData::DELETE_QUERY;

        // Deleted rows are mirrored before the query runs so that their data can still be read.
        if (!$isDelete) {
            $result = parent::query($query);
            if ($result === false) {
                return $result;
            }

            if ($parsedQueryData->queryType === ParsedQueryData::INSERT_UPDATE_QUERY) {
                $parsedQueryData->ids = [$this->insert_id];
            }
        }

        /** @var WpdbMirrorBridge $wpdbMirrorBridge */
        $wpdbMirrorBridge = $versionPressContainer->resolve(VersionPressServices::WPDB_MIRROR_BRIDGE);
        $this->runPreservingFields(fn() => $wpdbMirrorBridge->query($parsedQueryData));

        return $isDelete ? parent::query($query) : $result;
    }

    /**
     * Runs a query with the stock `wpdb` implementation, bypassing change tracking.
     *
     * @param string $query
     * @return int|bool
     */
    public function queryWithoutTracking($query)
    {
        return parent::query($query);
    }

    private function getMirrorBridge(): ?WpdbMirrorBridge
    {
        global $versionPressContainer;

        return $versionPressContainer === null
            ? null
            : $versionPressContainer->resolve(VersionPressServices::WPDB_MIRROR_BRIDGE);
    }

    /**
     * Restores the public result fields (`last_query`, `insert_id`, ...) after the callback so that queries
     * VersionPress runs while mirroring a change don't leak into what the caller of `insert()` etc. sees.
     */
    private function runPreservingFields(callable $callback): void
    {
        $fields = [
            'last_error' => $this->last_error,
            'last_query' => $this->last_query,
            'last_result' => $this->last_result,
            'rows_affected' => $this->rows_affected,
            'num_rows' => $this->num_rows,
            'insert_id' => $this->insert_id,
        ];

        try {
            $callback();
        } finally {
            foreach ($fields as $field => $value) {
                $this->$field = $value;
            }
        }
    }
}
