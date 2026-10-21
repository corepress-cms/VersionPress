<?php

use VersionPress\Utils\SystemInfo;

defined('ABSPATH') or die("Direct access not allowed");

// Pages without a menu item get no capability check from WordPress.
if (!current_user_can('manage_options')) {
    wp_die(esc_html__('Sorry, you are not allowed to access this page.', 'versionpress'), 403);
}

$vpSystemInfo = SystemInfo::getAllInfo();
$vpSections = [
    'summary' => __('Quick summary', 'versionpress'),
    'git-info' => __('Git', 'versionpress'),
    'permission-info' => __('Permissions', 'versionpress'),
    'wordpress-info' => __('WordPress', 'versionpress'),
    'php-info' => __('Server environment / PHP', 'versionpress'),
];
?>

<div class="wrap vp-system-info">
    <h1><?php esc_html_e('System info', 'versionpress'); ?></h1>

    <p><?php esc_html_e('Overview of this WordPress installation for VersionPress purposes.', 'versionpress'); ?></p>

    <p>
        <?php esc_html_e('Jump to:', 'versionpress'); ?>
        <?php foreach ($vpSections as $vpId => $vpTitle) : ?>
            <a href="#<?php echo esc_attr($vpId); ?>"><?php echo esc_html($vpTitle); ?></a>
        <?php endforeach; ?>
    </p>

    <?php foreach ($vpSections as $vpId => $vpTitle) : ?>
        <h2 id="<?php echo esc_attr($vpId); ?>"><?php echo esc_html($vpTitle); ?></h2>
        <pre><code><?php
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export -- the purpose of this page
            echo esc_html(var_export($vpSystemInfo[$vpId], true));
        ?></code></pre>
    <?php endforeach; ?>
</div>
