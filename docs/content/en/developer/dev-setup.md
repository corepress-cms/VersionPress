# Dev setup

This will set you up for VersionPress development. 👩‍💻 👨‍💻

## Getting started

Our approach is:

- You develop in **local tools** you're comfortable with – PhpStorm, VSCode, vim, ...
- The runtime (WordPress, PHP, MySQL, Composer, Git) is provided by [**wp-env**](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/), the official WordPress development environment. It runs in Docker; you don't need a local PHP, Composer, MAMP / XAMPP, etc.
- Common tasks are automated via **pnpm scripts**, for example, `pnpm test`.

This software is expected on your machine:

- Git
- Node.js 24+ and [pnpm](https://pnpm.io/installation)
- Docker (Docker Desktop, Docker Engine or Podman with Docker compatibility)

VersionPress targets **PHP 8.5+** and **WordPress 7.1+**; wp-env provides both.

## Project initialization

```sh
git clone https://github.com/versionpress/versionpress
cd versionpress
pnpm install
pnpm start                 # starts WordPress at http://localhost:8888
pnpm run composer install  # installs PHP dependencies inside the environment
pnpm run build:frontend    # builds the admin UI into the plugin
```

Log in at <http://localhost:8888/wp-admin> with `admin` / `password` and activate VersionPress on the _Plugins_ page, or run `pnpm run wp plugin activate versionpress`. You're now all set up! 🎉

## Development environment

The environment is configured in [`.wp-env.json`](https://github.com/versionpress/versionpress/blob/master/.wp-env.json). Create a `.wp-env.override.json` (Git-ignored) to customize it locally, for example, to change the port.

- VersionPress source files in `plugins/versionpress` are mapped to the site's `wp-content/plugins/versionpress`. Changes you make locally are immediately live.
- PHP tools run inside the `wordpress` container, which has the same PHP version and extensions as the site:
    - `pnpm run composer <args>` runs Composer in the plugin directory.
    - `pnpm run wp <args>` runs WP-CLI, e.g., `pnpm run wp option update blogname "Hello"`.
    - `pnpm run env run cli bash` opens a shell; the site lives in `/var/www/html`, so `git -C /var/www/html log` shows the history VersionPress created.
- WordPress is installed from the release ZIP rather than a Git checkout so that the site root starts without a Git repository, exactly like a real site.

`pnpm stop` stops the environment, `pnpm run destroy` removes it including the database for a fresh start.

> **Note**: Activating VersionPress currently modifies `wp-includes/class-wpdb.php` of the environment's WordPress; this is planned to be replaced by a `db.php` drop-in. Run `pnpm run destroy` to get a pristine WordPress again.

## Plugin development

VersionPress consists of PHP code implementing the core versioning logic and a React frontend. This section is about the former; the latter is described in [Frontend development](#frontend-development) below.

### Coding standards and static analysis

- Code is formatted according to [PSR-12](https://www.php-fig.org/psr/psr-12/). On top of that, the WordPress Coding Standards' security, i18n and database sniffs apply. The ruleset is in `plugins/versionpress/phpcs.xml.dist`.
- [PHPStan](https://phpstan.org/) with the [WordPress extension](https://github.com/szepeviktor/phpstan-wordpress) runs at level 5. Known pre-existing issues are recorded in `phpstan-baseline.neon`; new code should not add to it.

```sh
pnpm run lint:php       # PHPCS
pnpm run lint:php:fix   # PHPCBF, fixes formatting automatically
pnpm run analyse        # PHPStan
```

> **Note**: The WordPress security sniffs currently report as warnings while the WordPress integration layer (`setup-hooks.php`, `admin/`) is being rebuilt. CI fails on errors only.

Most VersionPress code is object-oriented and namespaced. The parts directly interacting with WordPress use WordPress conventions; for example, global functions are defined as `vp_register_hooks()`, not `registerHooks()`.

### Writing code

Please refer to the [Contributing code](https://github.com/versionpress/versionpress/blob/master/CONTRIBUTING.md#contributing-code) section in `CONTRIBUTING.md`.

### Debugging

wp-env ships with [Xdebug](https://xdebug.org/). Start the environment with `pnpm start --xdebug`, then configure your IDE to listen on port 9003 with this path mapping:

- `<your local path>/plugins/versionpress` → `/var/www/html/wp-content/plugins/versionpress`

For VSCode with the [PHP Debug extension](https://marketplace.visualstudio.com/items?itemName=xdebug.php-debug), a `launch.json` entry looks like this:

```json
{
  "name": "Listen for Xdebug (wp-env)",
  "type": "php",
  "request": "launch",
  "port": 9003,
  "pathMappings": {
    "/var/www/html/wp-content/plugins/versionpress": "${workspaceFolder}/plugins/versionpress"
  }
}
```

## Frontend development

VersionPress uses a JavaScript frontend implemented as a React app in the `./frontend` folder. `pnpm run build:frontend` builds it and copies the result to `plugins/versionpress/admin/public/gui`, where the plugin loads it from.

### Running frontend separately

For pure frontend development, it's more convenient to run it outside of the WordPress administration:

1. Make sure the site is running and VersionPress is activated in it. `frontend/src/config/config.local.ts` (created from `config.local.sample.ts` on the first build) points the app at <http://localhost:8888>.
2. Allow unauthenticated API access in the development site:

    ```sh
    pnpm run wp config set VERSIONPRESS_REQUIRE_API_AUTH false --raw
    ```

3. Run `pnpm --filter versionpress-frontend start`.

This launches [webpack dev server](https://webpack.js.org/configuration/dev-server/) at <http://localhost:8080>; source code edits are automatically reflected in the browser.

## Testing

See [Testing](testing.md).

## Production build

Run `pnpm run build`; it will produce a file like `dist/versionpress-3.0.2.zip`. The build currently needs Composer installed locally; it will move into the environment as part of the release pipeline rework.

The version number is based on the nearest Git tag and can also be something like `3.0.2-27-g0e1ce7f`, meaning that the closest tag is `3.0.2`, there have been 27 commits since then and the package was built from `0e1ce7f`. See [`git describe --tags`](https://git-scm.com/docs/git-describe#_examples) for more examples.

## Developing the dev setup

Simpler tasks are scripted directly in `package.json`; more complex ones live in the `./scripts` folder. See its README for more info.

The root is a [pnpm workspace](https://pnpm.io/workspaces) containing `frontend` and `scripts`; there is a single `pnpm-lock.yaml` at the root.

### Links to older documents

The previous, custom Docker-based setup is documented at the `4.0-alpha1` tag:

- [Dev-Setup.md](https://github.com/versionpress/versionpress/blob/4.0-alpha1/docs/Dev-Setup.md)
- [Testing.md](https://github.com/versionpress/versionpress/blob/4.0-alpha1/docs/Testing.md)
