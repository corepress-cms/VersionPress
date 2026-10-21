# Testing

Tests are a significant part of the VersionPress project; we care about writing and maintaining them. They live in `plugins/versionpress/tests` and there are several types of them, from unit to full end2end tests. They run with [PHPUnit](https://phpunit.de/) 12 inside the [wp-env](dev-setup.md#development-environment) environment.

> **Note**: the `./frontend` app has its own tests; this section is about core VersionPress tests (PHP code) only.

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

## Debugging tests

Start the environment with Xdebug enabled (`pnpm start --xdebug`), start listening in your IDE using the path mapping from [Debugging](dev-setup.md#debugging), set a breakpoint and run the test via `pnpm run phpunit --filter <TestName>`.

## Unit tests

Unit tests are best suited for small pieces of algorithmic functionality. For example, `IniSerializer` is covered by unit tests extensively.

## End2end tests

End2end tests exercise a WordPress site and check that VersionPress creates the right Git commits, that the database is in the correct state, etc. These tests are heavy and slow to run, but if they pass, there's a good chance that VersionPress works correctly.

!!! important "Pending port"
    The `End2End`, `Workflow`, `SynchronizerTests` and `LoadTests` suites install their own WordPress sites via `WpAutomation` and still target the previous custom Docker setup. They will be ported to wp-env (and the browser-driven parts to Playwright); until then they can't be run.

End2end tests use the concept of **workers**: each test itself is implemented once, but how, for example, a post is created or a user deleted is up to a specific worker. There are currently two types of workers:

1. **WP-CLI workers** – run WP-CLI commands against the test site.
2. **Selenium workers** – simulate a real user by clicking in a browser.

The worker type is configured by the `end2end-test-type` key in `tests/test-config.yml`.

## Other tests

The project has these other types of tests (folders in the `./plugins/versionpress/tests` folder and also test suite names in `phpunit.xml` so that you can run them using `--testsuite <SuiteName>`):

- `GitRepositoryTests` – test Git repository manipulation in `GitRepository`.
- `StorageTests` – test that entities are stored correctly as INI files.
- `SynchronizerTests` – test that given some INI files on disk, the database is in a correct state after synchronization runs.
- `LoadTests` – run with very few iterations by default; manually update their source files and execute them separately to properly exercise them.
- `Selenium` – a bit like end2end tests but for rarer cases, like VersionPress not being activated yet. Disabled; kept as a reference for the Playwright port.
- `Workflow` – exercise cloning and merging between environments.
