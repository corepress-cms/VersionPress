=== Versionist ===
Tags: version control, git, undo, staging, history
Requires at least: 7.1
Tested up to: 7.1
Requires PHP: 8.5
Stable tag: trunk
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Git-powered version control for WordPress: see the history of your site, undo any change, roll back, and synchronize environments.

== Description ==

Versionist tracks every change of your site – content, settings, plugins, themes – in a Git repository.

* See the history of your site, searchable and filterable.
* Undo individual changes, including database changes.
* Roll the whole site back to a previous state.
* Create staging environments (`wp versionist clone`) and merge them back (`wp versionist pull`).
* Use any Git tooling or hosting on top of it.

Versionist is a **Developer Preview**. Keep a backup of your site and see the documentation for supported workflows and plugins.

Versionist is a fork of [VersionPress](https://github.com/versionpress/versionpress), modernized for current WordPress and PHP. It keeps VersionPress's data format, so sites tracked by VersionPress and plugin definitions written for it keep working.

= Requirements =

* Git installed on the server and `proc_open()` enabled for PHP.
* Write access to the site's files.
* No other plugin using the `wp-content/db.php` drop-in. Versionist tracks database changes through it and does not modify WordPress core files.

== Frequently Asked Questions ==

= Where is the history stored? =

In a Git repository in the root of your site. Database entities are stored as files in `wp-content/vpdb`.

= What happens when I deactivate Versionist? =

The Git repository stays on the server. Uninstalling the plugin moves it to `wp-content/vpbackups`.

== Changelog ==

= Unreleased =

* Requires PHP 8.5 and WordPress 7.1.
* Tracks database changes through a `db.php` drop-in instead of modifying WordPress core.
* New admin screen built with WordPress components.
* Data model updated for the block editor (templates, global styles, navigation, patterns, fonts).
