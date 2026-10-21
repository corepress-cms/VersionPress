# Installation and Uninstallation

Versionist ships as a standard plugin but there are two important things to be aware of:

1. Versionist has **stricter system requirements** than usual
2. Its **activation is a two-step process**

Both things are important, please read on.

## System requirements

The server environment must match certain requirements, some of which are checked automatically on Versionist activation. We also recommend some other setup steps below.

<figure style="width: 80%;">
  <img src="https://cloud.githubusercontent.com/assets/101152/26038379/7f8dbaf0-3907-11e7-92f4-3d211ae0d882.png" alt="Pre-activation check" />
  <figcaption>Pre-activation check performed by Versionist</figcaption>
</figure>

!!! warning "Important"
    Versionist is a lot more involved than most other WordPress plugins. Please pay attention to this section before proceeding with installation.

Minimum system requirements are (as a general rule, **we recommend using the latest versions of everything**):

- WordPress 7.1 or higher
- PHP 8.5 or higher
- Git 1.9 or higher (2.13 or newer recommended)
- Apache, nginx or IIS 7+ web server
- `proc_open()` enabled
- Write permissions in the site root and everywhere under it
- Standard WordPress configuration system (`wp-config.php`)
- No other plugin using the `wp-content/db.php` drop-in

On top of that, if you want to use the [multi-instance / sync features](../sync/index.md) of VersionPress 2.0, probably even more control over the requirement will be required. In practice, this means that a custom server / VPS is your best bet. See also the section on [hosting providers](../integrations/hosts.md).

Here are notes on some of the requirements:

### Git

Versionist takes a strategic dependency on [Git](http://git-scm.com/) which provides [many benefits](../feature-focus/git.md) but also requires this tool to be installed on the server and accessible from PHP. Make sure that `proc_open()` is enabled on the server and that the Git installation is in the PATH (if it's not, you can tell Versionist where to find the binary via [Configuration](./configuration.md)).

Git **1.9** and newer are supported. Do not attempt to make Versionist run with older releases (1.7 and 1.8 are still quite popular), there are known issues with them.

### Supported web servers

We recommend Apache or nginx (as [WordPress itself](https://wordpress.org/about/requirements/)) but almost any web server should work. Just pay attention to two things:

1. **Write permissions**. The user that runs PHP and the eventual Git process needs to have write access into the locations listed below and the `sys_get_temp_dir()`. Initialization page checks this automatically and the [system info page](../troubleshooting/system-info-page.md) has a dedicated section on permissions if you need more info.
    - IIS users, please [read this page](../troubleshooting/iis.md).
2. **Access rules**. The locations listed below should be protected against direct requests.

The sensitive locations are:

- `/wp-content/vpdb`
- `/wp-content/vpbackups`
- `/wp-content/plugins/versionist`
- `/.git`

We ship `.htaccess` rules for Apache, `web.config` rules for IIS and `wp-content/plugins/versionist/versionist-nginx.conf` template for nginx but please confirm manually that direct access to e.g. `yoursite/.git/config` is prevented.

### PHP 8.5

Versionist requires PHP 8.5 or newer and WordPress 7.1 or newer. WordPress enforces both when the plugin is activated.

### Database drop-in

Versionist tracks database changes through a [drop-in](https://developer.wordpress.org/reference/functions/require_wp_db/), `wp-content/db.php`, which makes WordPress use a Versionist-enhanced version of its `wpdb` class. Versionist doesn't modify WordPress core files.

Only one plugin can use this drop-in. If another plugin already does (for example, Query Monitor or some caching plugins), the pre-activation check fails; remove the other drop-in first. If a different drop-in appears while Versionist is active, Versionist shows an admin notice and stops tracking database changes until it is removed.

### Project structure

Some advanced users like having WordPress in its own directory or move plugins, themes or uploads in another directory. Versionist supports following scenarios:

- giving WordPress its own directory
- renaming `wp-content`, `plugins` or `uploads` directories.

See [custom project structure](../feature-focus/custom-project-structure.md) page for more.

## Installation

Versionist can be downloaded from the releases of its GitHub repository. When you have the ZIP file, the installation is pretty standard:

1. Log in to the admin screens
2. Go to *Plugins > Add New > Upload*
3. Choose or drag&drop `versionist-<version>.zip` to that page
4. Click *Install Now*
5. Activate the plugin
6. **Finish the activation process** by going into the new Versionist section in the administration and clicking the *Activate* button

The last step is important, otherwise Versionist wouldn't be able to track changes. The on-screen instructions will guide you through it.

Upon successful activation, you should see a screen like this:

<figure style="width: 80%;">
  <img src="https://cloud.githubusercontent.com/assets/101152/26038360/1f325f26-3907-11e7-9993-0e25ff3299d7.png" alt="Versionist activated" />
  <figcaption>Versionist successfully activated</figcaption>
</figure>

## Update

Since [VersionPress 3.0](../release-notes/3.0.md), the simplest way to update is to run the `wp versionist update` command, e.g.:

```
wp versionist update ./versionist.zip
```

This will keep the Git repo and continue tracking the site fine, however, keep in mind that the original history becomes unactionable: you will not be able to undo old changes or roll back to previous states. ([Full migrations](https://github.com/versionpress/versionpress/issues/275) are on our roadmap).

If you cannot use the WP-CLI update method, these are the manual steps:

1. Put the site in a maintenance mode.
2. Deactivate Versionist (just deactivate, do not uninstall).
3. Delete the contents of `wp-content/plugins/versionist` and extract the new version there.
4. Activate & initialize the plugin again.
5. Disable the maintenance mode.

The difference from the automated method is that the internal representation of the database has been regenerated from scratch so you won't be able to track the history of database entities easily. We will improve the GUI update method in the future.

## Uninstallation

Uninstallation is a standard two-step process:

1. You first **deactivate** the plugin on the *Plugins* admin screen
2. You then **delete** the plugin to get rid of all its files
    - :warning: If you didn't deactivate the plugin via admin screens and manually removed the `wp-content/plugins/versionist` folder (which is NOT recommended), WordPress keeps working with its standard database class but you should also delete `wp-content/db.php`.
3. *Optional:* Manually download or delete a repository backup which was created under `wp-content/backup`.

There are two important things to note:

- Once Versionist is deactivated, **it cannot be fully reactivated again on the same repository**. This means that while you can initialize Versionist again and the presence of the old repository will not be a problem, features like Undo or Rollback will only be available for *new* commits, created by the current activation of Versionist. This is technical limitation that is not easy to overcome.
- **On uninstallation, the Git repository is moved to a backup folder** under `wp-content/vpbackups`. You can download or recover it from there manually.
    - Note: Versionist will only remove / backup the repository if it detects that it was Versionist-initiated repository. If you created the Git repository manually before installing Versionist the repository will not be touched.

## Versionist states at a glance

To sum up the previous text, here are the states that the site can be in:

| State | Git repo exists? | Versionist tracking changes? |
| :------------- | :-----: | :-----: |
| WP site without Versionist | No<sup>1)</sup> | No |
| Versionist installed | No | No |
| Versionist activated on the *Plugins* screen | **Still no** | **Still no** |
| Activation finished on Versionist screen - the plugin is *active* | **Yes** | **Yes** |
| Deactivated (on plugin admin screen) | Yes | **No** |
| Versionist reactivated on the *Plugins* screen (similar to step 3) | Yes (but obsolete) | **Still no** |
| Fully activated again (similar to step 4) | Yes | **Yes** |
| Uninstalled | **No** (backed up) | No |

<sup>1)</sup> The repo might exist if you created it manually or if Versionist was previously installed. It is not a problem – Versionist will happily add commits to the existing repository but a common scenario is that there is no default Git repository and Versionist creates one.
