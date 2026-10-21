# Testing

Tests are a significant part of the Versionist project; we care about writing and maintaining them. They live in `plugins/versionist/tests` and there are several types of them, from unit to full end2end tests. They run with [PHPUnit](https://phpunit.de/) 12 inside the [wp-env](dev-setup.md#development-environment) environment.

Besides the PHP tests, the admin UI (`admin-ui/`) has unit tests (`pnpm run test:js`, [Vitest](https://vitest.dev/)) and the whole plugin has browser tests (`pnpm run test:e2e`, [Playwright](https://playwright.dev/)), see [Browser tests](#browser-tests).

## Running tests from command line

1. Set up the environment as described in [Dev Setup](dev-setup.md) (`pnpm install`, `pnpm start`, `pnpm run composer install`).
2. Run `pnpm run test:unit` as a sanity check – this should pass in seconds.
3. Run `pnpm test` to run all suites that don't need a dedicated WordPress test site (`Unit`, `StorageTests`, `GitRepositoryTests`). This is what CI runs.

### Customizing what tests run

`pnpm run phpunit` runs PHPUnit with the project's `phpunit.xml` and accepts any [PHPUnit option](https://docs.phpunit.de/en/12.5/textui.html):

```sh
# Pick a test suite (see phpunit.xml):
pnpm run phpunit --testsuite Unit

# Filter down further:
pnpm run phpunit --testsuite Unit --filter CursorTest

# Stop on the first error or failure:
pnpm run phpunit --stop-on-failure

# Create your own phpunit.xml (phpunit.*.xml is Git-ignored)
pnpm run phpunit -c tests/phpunit.custom.xml
```

## Tests that need a WordPress site

The `End2End`, `SynchronizerTests` and `Workflow` suites install their own WordPress sites (via `WpAutomation`, configured in `tests/test-config.yml`) inside a separate, isolated environment so that they never touch your development site:

```sh
pnpm run test:env   # starts .wp-env.test.json (port 8889); installs Git, the MySQL client and WP-CLI
pnpm run test:wp    # runs the suites; append PHPUnit options, e.g. --filter PostsTest
```

The first run downloads WordPress and WP-CLI, so it takes a bit longer. The test site lives in `/var/www/html/wptest` of that environment's `wordpress` container; `pnpm run env run --config=.wp-env.test.json wordpress bash` opens a shell there to inspect its files and Git history.

## Browser tests

Playwright tests in `e2e/specs` drive the admin screen and the block editor in Chromium against a third environment (`.wp-env.e2e.json`, port 8890) where Versionist is activated automatically:

```sh
pnpm --filter versionist-e2e run install-browsers   # once
pnpm run build:admin
pnpm run e2e:env
pnpm run test:e2e
```

## Debugging tests

Start the environment with Xdebug enabled (`pnpm start --xdebug`), start listening in your IDE using the path mapping from [Debugging](dev-setup.md#debugging), set a breakpoint and run the test via `pnpm run phpunit --filter <TestName>`.

## Unit tests

Unit tests are best suited for small pieces of algorithmic functionality. For example, `IniSerializer` is covered by unit tests extensively.

## End2end tests

End2end tests exercise a WordPress site and check that Versionist creates the right Git commits, that the database is in the correct state, etc. These tests are heavy and slow to run, but if they pass, there's a good chance that Versionist works correctly.

Run them as described in [Tests that need a WordPress site](#tests-that-need-a-wordpress-site).

End2end tests use the concept of **workers**: each test itself is implemented once, but how, for example, a post is created or a user deleted is up to a specific worker. There are currently two types of workers:

1. **WP-CLI workers** – run WP-CLI commands against the test site.
2. **Selenium workers** – simulate a real user by clicking in a browser. These are not maintained anymore; browser flows are covered by the [Playwright tests](#browser-tests) instead.

The worker type is configured by the `end2end-test-type` key in `tests/test-config.yml`.

## Other tests

The project has these other types of tests (folders in the `./plugins/versionist/tests` folder and also test suite names in `phpunit.xml` so that you can run them using `--testsuite <SuiteName>`):

- `GitRepositoryTests` – test Git repository manipulation in `GitRepository`.
- `StorageTests` – test that entities are stored correctly as INI files.
- `SynchronizerTests` – test that given some INI files on disk, the database is in a correct state after synchronization runs.
- `LoadTests` – run with very few iterations by default; manually update their source files and execute them separately to properly exercise them.
- `Selenium` – disabled; superseded by the Playwright tests in `e2e/`.
- `Workflow` – exercise cloning and merging between environments.
