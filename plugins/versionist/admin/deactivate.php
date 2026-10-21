<?php

use Versionist\Hooks\Lifecycle;
use Versionist\Utils\UninstallationUtil;

defined('ABSPATH') or die("Direct access not allowed");

// Pages without a menu item get no capability check from WordPress.
if (!current_user_can('activate_plugins')) {
    wp_die(esc_html__('Sorry, you are not allowed to access this page.', 'versionist'), 403);
}

$vpDeactivationButton = function ($label, $action, $type, $cssClass = '') {
    ?>
    <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" class="<?php echo esc_attr($cssClass); ?>">
        <input type="hidden" name="action" value="<?php echo esc_attr($action); ?>" />
        <?php
        wp_nonce_field(Lifecycle::getDeactivationNonceAction());
        submit_button($label, $type, $action, false, ['id' => $action]);
        ?>
    </form>
    <?php
};
?>

<div class="wrap vp-deactivation">
    <h1><?php esc_html_e('Versionist deactivation', 'versionist'); ?></h1>

    <div class="notice notice-warning">
        <p><?php esc_html_e('You are about to deactivate Versionist. Be aware that:', 'versionist'); ?></p>

        <ul>
            <li>
                <span class="dashicons dashicons-info warning-color"></span>
                <?php
                echo wp_kses_post(__(
                    'Later installations of Versionist <strong>will not be able to undo or roll back changes</strong> done by this installation. The changes are still in the repository if you want to inspect them, e.g. using Git command line tools.',
                    'versionist'
                ));
                ?>
            </li>
            <li>
                <span class="dashicons dashicons-yes ok-color"></span>
                <?php echo wp_kses_post(__('Deactivation <strong>keeps the Git repository on the server</strong>. You can, for example, download it for local inspection.', 'versionist')); ?>
            </li>
            <li>
                <span class="dashicons dashicons-yes ok-color"></span>
                <?php echo wp_kses_post(__('You <strong>can activate Versionist again</strong> and the current Git repository will not cause any trouble; the new installation just cannot undo the old changes.', 'versionist')); ?>
            </li>
            <?php if (UninstallationUtil::uninstallationShouldRemoveGitRepo()) : ?>
                <li>
                    <span class="dashicons dashicons-info warning-color"></span>
                    <?php echo wp_kses_post(__('If you <strong>delete</strong> Versionist later on the Plugins screen, the Git repository is moved to the <code>wp-content/vpbackups</code> folder. The site will appear unversioned but you can restore the repository from there.', 'versionist')); ?>
                </li>
            <?php endif; ?>
        </ul>

        <div class="deactivation-buttons">
            <?php
            $vpDeactivationButton(__('Cancel', 'versionist'), 'cancel_deactivation', 'secondary', 'cancel-deactivation');
            $vpDeactivationButton(__('Confirm deactivation', 'versionist'), 'confirm_deactivation', 'primary');
            ?>
        </div>
    </div>
</div>
