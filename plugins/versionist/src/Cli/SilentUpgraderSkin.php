<?php

namespace Versionist\Cli;

/**
 * Silence! I'll kill you!
 */
class SilentUpgraderSkin extends \WP_Upgrader_Skin
{
    public function header()
    {
    }

    public function footer()
    {
    }

    public function feedback($feedback, ...$args)
    {
    }
}
