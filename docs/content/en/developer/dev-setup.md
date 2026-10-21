# Dev setup

This will set you up for Versionist development. 👩‍💻 👨‍💻

## Getting started

Our approach is:

- You develop in **local tools** you're comfortable with – PhpStorm, VSCode, vim, ...
- The runtime (WordPress, PHP, MySQL, Composer, Git) is provided by [**wp-env**](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/), the official WordPress development environment. It runs in Docker; you don't need a local PHP, Composer, MAMP / XAMPP, etc.
- Common tasks are automated via **pnpm scripts**, for example, `pnpm test`.

This software is expected on your machine:

- Git
- Node.js 24+ and [pnpm](https://pnpm.io/installation)
- Docker (Docker Desktop, Docker Engine or Podman with Docker compatibility)

Versionist targets **PHP 8.5+** and **WordPress 7.1+**; wp-env provides both.

## Project initialization

```sh
git clone <repository URL> versionist
cd versionist
pnpm install
pnpm start                 # starts WordPress at http://localhost:8888
pnpm run composer install  # installs PHP dependencies inside the environment
pnpm run build:admin       # builds the admin UI into the plugin
```

Log in at <http://localhost:8888/wp-admin> with `admin` / `password` and activate Versionist on the _Plugins_ page, or run `pnpm run wp plugin activate versionist`. You're now all set up! 🎉

## Development environment

The environment is configured in [`.wp-env.json`](../../../../.wp-env.json). Create a `.wp-env.override.json` (Git-ignored) to customize it locally, for example, to change the port.

- Versionist source files in `plugins/versionist` are mapped to the site's `wp-content/plugins/versionist`. Changes you make locally are immediately live.
- PHP tools run inside the `wordpress` container, which has the same PHP version and extensions as the site:
    - `pnpm run composer <args>` runs Composer in the plugin directory.
    - `pnpm run wp <args>` runs WP-CLI, e.g., `pnpm run wp option update blogname "Hello"`.
    - `pnpm run env run cli bash` opens a shell; the site lives in `/var/www/html`, so `git -C /var/www/html log` shows the history Versionist created.
- WordPress is installed from the release ZIP rather than a Git checkout so that the site root starts without a Git repository, exactly like a real site.

`pnpm stop` stops the environment, `pnpm run destroy` removes it including the database for a fresh start.

Versionist tracks database changes through the `wp-content/db.php` drop-in; WordPress core files stay untouched (`pnpm run wp core verify-checksums` passes).

## Plugin development

Versionist consists of PHP code implementing the core versioning logic and a React admin UI. This section is about the former; the latter is described in [Admin UI development](#admin-ui-development) below.

### Coding standards and static analysis

- Code is formatted according to [PSR-12](https://www.php-fig.org/psr/psr-12/). On top of that, the WordPress Coding Standards' security, i18n and database sniffs apply. The ruleset is in `plugins/versionist/phpcs.xml.dist`.
- [PHPStan](https://phpstan.org/) with the [WordPress extension](https://github.com/szepeviktor/phpstan-wordpress) runs at level 5. Known pre-existing issues are recorded in `phpstan-baseline.neon`; new code should not add to it.

```sh
pnpm run lint:php       # PHPCS
pnpm run lint:php:fix   # PHPCBF, fixes formatting automatically
pnpm run analyse        # PHPStan
```

> **Note**: The WordPress security sniffs currently report as warnings while the WordPress integration layer (`setup-hooks.php`, `admin/`) is being rebuilt. CI fails on errors only.

Most Versionist code is object-oriented and namespaced. The parts directly interacting with WordPress use WordPress conventions; for example, global functions are defined as `vp_register_hooks()`, not `registerHooks()`.

### Writing code

Please refer to the [Contributing code](../../../../CONTRIBUTING.md#contributing-code) section in `CONTRIBUTING.md`.

### Debugging

wp-env ships with [Xdebug](https://xdebug.org/). Start the environment with `pnpm start --xdebug`, then configure your IDE to listen on port 9003 with this path mapping:

- `<your local path>/plugins/versionist` → `/var/www/html/wp-content/plugins/versionist`

For VSCode with the [PHP Debug extension](https://marketplace.visualstudio.com/items?itemName=xdebug.php-debug), a `launch.json` entry looks like this:

```json
{
  "name": "Listen for Xdebug (wp-env)",
  "type": "php",
  "request": "launch",
  "port": 9003,
  "pathMappings": {
    "/var/www/html/wp-content/plugins/versionist": "${workspaceFolder}/plugins/versionist"
  }
}
```

## Admin UI development

The Versionist admin screen is a React app in `./admin-ui`, built with [`@wordpress/scripts`](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-scripts/) on top of `@wordpress/components`, `@wordpress/dataviews` and `@wordpress/api-fetch`. It talks to the plugin through its REST API (`/wp-json/versionist/v1`).

- `pnpm run build:admin` builds it into `plugins/versionist/admin/public/build`, where the plugin loads it from.
- `pnpm --filter versionist-admin-ui start` rebuilds on every change; reload the Versionist screen in wp-admin to see it.
- `pnpm run lint:js` lints and type-checks it, `pnpm run test:js` runs its unit tests.

## Testing

See [Testing](testing.md).

## Production build

Run `pnpm run build`; it will produce a file like `dist/versionist-1.0.0.zip`. It builds the admin UI and then runs `plugins/versionist/bin/build-release.php` inside the environment, which installs the production dependencies, prefixes their namespaces with [Strauss](https://github.com/BrianHenryIE/strauss) so they cannot clash with other plugins, and leaves out everything listed in `plugins/versionist/.distignore`.

The version number is based on the nearest Git tag and can also be something like `3.0.2-27-g0e1ce7f`, meaning that the closest tag is `3.0.2`, there have been 27 commits since then and the package was built from `0e1ce7f`. See [`git describe --tags`](https://git-scm.com/docs/git-describe#_examples) for more examples.

## Developing the dev setup

Simpler tasks are scripted directly in `package.json`; more complex ones live in the `./scripts` folder. See its README for more info.

The root is a [pnpm workspace](https://pnpm.io/workspaces) containing `admin-ui`, `e2e` and `scripts`; there is a single `pnpm-lock.yaml` at the root.

### Links to older documents

The previous, custom Docker-based setup is documented at the `4.0-alpha1` tag:

- [Dev-Setup.md](https://github.com/versionpress/versionpress/blob/4.0-alpha1/docs/Dev-Setup.md)
- [Testing.md](https://github.com/versionpress/versionpress/blob/4.0-alpha1/docs/Testing.md)
