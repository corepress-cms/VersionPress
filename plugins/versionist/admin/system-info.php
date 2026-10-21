<?php

use Versionist\Utils\SystemInfo;

defined('ABSPATH') or die("Direct access not allowed");

// Pages without a menu item get no capability check from WordPress.
if (!current_user_can('manage_options')) {
    wp_die(esc_html__('Sorry, you are not allowed to access this page.', 'versionist'), 403);
}

$vpSystemInfo = SystemInfo::getAllInfo();
$vpSections = [
    'summary' => __('Quick summary', 'versionist'),
    'git-info' => __('Git', 'versionist'),
    'permission-info' => __('Permissions', 'versionist'),
    'wordpress-info' => __('WordPress', 'versionist'),
    'php-info' => __('Server environment / PHP', 'versionist'),
];
?>

<div class="wrap vp-system-info">
    <h1><?php esc_html_e('System info', 'versionist'); ?></h1>

    <p><?php esc_html_e('Overview of this WordPress installation for Versionist purposes.', 'versionist'); ?></p>

    <p>
        <?php esc_html_e('Jump to:', 'versionist'); ?>
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
