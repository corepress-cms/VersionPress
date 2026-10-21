# Versionist

Versionist is Git-powered version control for WordPress. You can:

- See the history of your site, searchable and filterable
- Undo anything, including database changes
- Roll back to previous states
- Create safe staging environments: `wp versionist clone --name=staging`
- Merge databases: `wp versionist pull --from=staging`
- Push to GitHub or implement any workflow you like

Versionist is a fork of [VersionPress](https://github.com/versionpress/versionpress), modernized for current WordPress (7.1+) and PHP (8.5+): it tracks database changes through a `db.php` drop-in instead of patching WordPress core, has a new admin screen built with WordPress components, and is updated for the block editor. It keeps VersionPress's data format, so sites tracked by VersionPress and plugin definitions (`.versionpress/` folders) written for it keep working.

## Developer Preview

Versionist works fine on simpler sites and you're encouraged to try it in your development workflows, but it is not production-ready yet. Keep a backup of your site.

## Get started

To **use** Versionist:

1. Download the plugin ZIP from the releases of this repository (or build it with `pnpm run build`).
2. Install it on your WordPress site.
3. Open the _Versionist_ screen in the WordPress administration and activate it, or run `wp versionist activate`.

To **develop** Versionist:

1. Set up your development environment as per [dev-setup.md](docs/content/en/developer/dev-setup.md).
2. Send pull requests.

See [CONTRIBUTING.md](./CONTRIBUTING.md) for more.

## Documentation

The user documentation in [`docs/content`](docs/content/en) is inherited from VersionPress and is being updated for Versionist.

## License

GPL-3.0-or-later. Versionist is based on VersionPress, © VersionPress contributors.
