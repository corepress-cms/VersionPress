<?php

namespace Versionist\Initialization;

/**
 * Constants listing what options Versionist uses. Constants with the prefix `USER_META_` go into
 * the `user_meta` table, the others go to standard `options` table.
 */
class VersionistOptions
{
    /**
     * Similar to WP's show_welcome_panel. If `1`, show welcome panel above the main Versionist table.
     */
    public const USER_META_SHOW_WELCOME_PANEL = 'vp_show_welcome_panel';
}
