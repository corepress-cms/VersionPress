<?php

// Constants that Versionist or the site's wp-config.php define at runtime. The values are
// placeholders; phpstan.neon.dist lists them as dynamic so PHPStan only relies on their types.

define('VERSIONIST_PLUGIN_FILE', __DIR__ . '/../versionist.php');
define('VERSIONIST_PLUGIN_DIR', dirname(__DIR__));
define('VERSIONIST_TEMP_DIR', '');
define('VERSIONIST_ACTIVATION_FILE', '');
define('VERSIONIST_COMMIT_MESSAGE_PREFIX', '');
define('VERSIONIST_ACTIVATED', true);
define('VP_PROJECT_ROOT', '');
define('VP_VPDB_DIR', '');
define('VP_INDEX_DIR', '');
define('VP_GIT_BINARY', '');
define('VP_WP_CLI_BINARY', '');
define('VP_DEACTIVATING', false);
define('VP_DEBUG', false);
define('VP_MAINTENANCE', false);
define('VP_ENVIRONMENT', '');
define('VP_ENVIRONMENT_TAG', '');
define('VP_VERSION_TAG', '');

// WordPress core constants not covered by the WordPress PHPStan extension.
define('WPINC', 'wp-includes');
define('DB_NAME', '');
define('DB_USER', '');
define('DB_PASSWORD', '');
define('DB_HOST', '');
