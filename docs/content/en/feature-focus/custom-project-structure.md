# Custom Project Structure

Some advanced users like having WordPress in its own directory or move plugins, themes or uploads in another directory. Versionist supports some scenarios. Just remember that all files related to the site have to be under the project root ([`VP_PROJECT_ROOT`](../getting-started/configuration.md#vp_project_root)).

!!! warning
    You need to adjust your project structure _before_ fully initializing Versionist. The recommended procedure is:

     * Customize your WordPress site structure.
     * Install and active Versionist, the plugin – do _not_ go through the full initialization yet.
     * Follow the instructions below, i.e., set some config constant like `VP_PROJECT_ROOT`.
     * Initialize Versionist.

## Giving WordPress its own directory

You can move WordPress into its own directory by following [instructions on Codex](https://codex.wordpress.org/Giving_WordPress_Its_Own_Directory). However, there is one extra step. You need to define `VP_PROJECT_ROOT` constant to let Versionist know where it should create the repository. See the [configuration page](../getting-started/configuration.md#vp_project_root) for instructions.

!!! note ".git directory"
    Be sure that the `.git` directory stays in the root directory if the project is already versioned.

## Moving wp-content, plugin or uploads directories

It is possible to move these folders by following [instructions on Codex](https://codex.wordpress.org/Editing_wp-config.php#Moving_wp-content_folder). Be sure that you define constants referencing directories in the `wp-config.common.php` and constants containing URLs in the `wp-config.php` if Versionist is already active.

## Moving VPDB directory

You can also rename or move the directory where Versionist saves all its data. Use constant `VP_VPDB_DIR` to get it done. See the [configuration page](../getting-started/configuration.md#vp_vpdb_dir) for instructions.

!!! important "Note"
    It will NOT be possible to undo changes before moving the directory.
