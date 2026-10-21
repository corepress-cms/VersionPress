<?php

namespace VersionPress\Initialization;

use VersionPress\Utils\FileSystem;

/**
 * Manages the `wp-content/db.php` drop-in that makes WordPress use {@see \VersionPress\Database\VersionPressWpdb}.
 *
 * Only one plugin can own the drop-in, so VersionPress never overwrites or removes a `db.php` it didn't create.
 */
class DbDropIn
{
    /** Identifies a drop-in installed by VersionPress; part of the template's header. */
    private const MARKER = 'VersionPress database drop-in';

    /** Added to `class-wpdb.php` by VersionPress versions that patched core instead of using the drop-in. */
    private const LEGACY_PATCH_MARKER = '// Enhanced by VersionPress';

    public static function getPath(): string
    {
        return WP_CONTENT_DIR . '/db.php';
    }

    public static function isInstalled(): bool
    {
        return file_exists(self::getPath()) && str_contains(file_get_contents(self::getPath()), self::MARKER);
    }

    /**
     * True if `db.php` exists and belongs to something else, e.g. Query Monitor or a caching plugin.
     */
    public static function isOccupiedByAnotherDropIn(): bool
    {
        return file_exists(self::getPath()) && !self::isInstalled();
    }

    public static function canBeInstalled(): bool
    {
        if (self::isOccupiedByAnotherDropIn()) {
            return false;
        }

        return file_exists(self::getPath()) ? is_writable(self::getPath()) : is_writable(WP_CONTENT_DIR);
    }

    /**
     * Installs (or refreshes) the drop-in and removes the legacy `class-wpdb.php` patch if present.
     *
     * @throws \RuntimeException If another plugin owns `db.php`.
     */
    public static function install(): void
    {
        if (self::isOccupiedByAnotherDropIn()) {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- logged, not rendered as HTML
            throw new \RuntimeException(sprintf('%s belongs to another plugin.', self::getPath()));
        }

        $dropIn = str_replace(
            '{{versionpress-dir}}',
            basename(VERSIONPRESS_PLUGIN_DIR),
            file_get_contents(__DIR__ . '/db-dropin.tpl.php')
        );
        file_put_contents(self::getPath(), $dropIn);

        self::removeLegacyWpdbPatch();
    }

    public static function uninstall(): void
    {
        if (self::isInstalled()) {
            FileSystem::remove(self::getPath());
        }
    }

    /**
     * Older VersionPress versions rewrote `wp-includes/class-wpdb.php` and kept the original next to it.
     */
    private static function removeLegacyWpdbPatch(): void
    {
        $wpdbClassPath = ABSPATH . WPINC . '/class-wpdb.php';
        $originalPath = $wpdbClassPath . '.original';

        if (!file_exists($originalPath)) {
            return;
        }

        if (str_contains(file_get_contents($wpdbClassPath), self::LEGACY_PATCH_MARKER)) {
            rename($originalPath, $wpdbClassPath);
        }
    }
}
