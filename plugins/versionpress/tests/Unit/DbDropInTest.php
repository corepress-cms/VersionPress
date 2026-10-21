<?php

namespace VersionPress\Tests\Unit;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use VersionPress\Initialization\DbDropIn;

/**
 * Runs in separate processes because DbDropIn is built on WordPress path constants.
 */
#[RunTestsInSeparateProcesses]
class DbDropInTest extends TestCase
{
    /** @var vfsStreamDirectory */
    private $site;

    protected function setUp(): void
    {
        $this->site = vfsStream::setup('site', null, [
            'wp-content' => ['plugins' => ['versionpress' => []]],
            'wp-includes' => ['class-wpdb.php' => "<?php\nclass wpdb {}\n"],
        ]);

        define('ABSPATH', vfsStream::url('site') . '/');
        define('WPINC', 'wp-includes');
        define('WP_CONTENT_DIR', vfsStream::url('site/wp-content'));
        define('VERSIONPRESS_PLUGIN_DIR', vfsStream::url('site/wp-content/plugins/versionpress'));
    }

    #[Test]
    public function installWritesDropInPointingToThePluginBootstrap()
    {
        $this->assertTrue(DbDropIn::canBeInstalled());

        DbDropIn::install();

        $this->assertTrue(DbDropIn::isInstalled());
        $this->assertStringContainsString(
            "'/versionpress/bootstrap.php'",
            file_get_contents(WP_CONTENT_DIR . '/db.php')
        );
    }

    #[Test]
    public function uninstallRemovesOwnDropIn()
    {
        DbDropIn::install();

        DbDropIn::uninstall();

        $this->assertFileDoesNotExist(WP_CONTENT_DIR . '/db.php');
    }

    #[Test]
    public function foreignDropInIsNeitherOverwrittenNorRemoved()
    {
        $foreignDropIn = "<?php\n// Query Monitor Database Class\n";
        file_put_contents(WP_CONTENT_DIR . '/db.php', $foreignDropIn);

        $this->assertTrue(DbDropIn::isOccupiedByAnotherDropIn());
        $this->assertFalse(DbDropIn::isInstalled());
        $this->assertFalse(DbDropIn::canBeInstalled());

        DbDropIn::uninstall();
        $this->assertSame($foreignDropIn, file_get_contents(WP_CONTENT_DIR . '/db.php'));

        $this->expectException(\RuntimeException::class);
        DbDropIn::install();
    }

    #[Test]
    public function installRestoresWpdbPatchedByOlderVersions()
    {
        $wpdbClassPath = ABSPATH . WPINC . '/class-wpdb.php';
        $original = file_get_contents($wpdbClassPath);
        file_put_contents($wpdbClassPath . '.original', $original);
        file_put_contents($wpdbClassPath, "<?php // Enhanced by VersionPress\nclass wpdb { /* patched */ }\n");

        DbDropIn::install();

        $this->assertSame($original, file_get_contents($wpdbClassPath));
        $this->assertFileDoesNotExist($wpdbClassPath . '.original');
    }

    #[Test]
    public function unrelatedWpdbBackupIsLeftAlone()
    {
        $wpdbClassPath = ABSPATH . WPINC . '/class-wpdb.php';
        $original = file_get_contents($wpdbClassPath);
        file_put_contents($wpdbClassPath . '.original', 'something else');

        DbDropIn::install();

        $this->assertSame($original, file_get_contents($wpdbClassPath));
        $this->assertFileExists($wpdbClassPath . '.original');
    }

    #[Test]
    public function cannotBeInstalledIntoReadOnlyContentDirectory()
    {
        $this->site->getChild('wp-content')->chmod(0555);

        $this->assertFalse(DbDropIn::canBeInstalled());
    }
}
