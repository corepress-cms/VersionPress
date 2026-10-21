# Versionist dev scripts

Scripts that have outgrown the one-liners in `package.json`.

## Dev setup

`pnpm install` is run as part of root's post-install script so you should be fine.

Scripts can be run during development like this:

```
node -r ts-node/register build.ts
```

The repo-root `package.json` scripts then call them like this:

```
node -r ./scripts/node_modules/ts-node/register scripts/build.ts
```

## Debugging scripts

Use a `pnpm run debug-script ...` or manually add `--inspect-brk`:

```
node -r ts-node/register --inspect-brk build.ts
```

In VSCode, use the "Node attach" configuration.

## About the `changelog` script

The script is used when [preparing a release](../docs/content/en/developer/development-process.md#release-process).

How to use it:

1. Create a new [personal access token](https://github.com/settings/tokens) on GitHub. It only needs the `public_repo` scope.
2. Copy `.env.example` to `.env` and put the token there.
3. Run the tool like this: `pnpm run changelog 4.0-beta..master`

The CLI output is directly paste-able to GitHub issues, however, if you want to paste it to a Markdown document (e.g., release notes), add the `--format` flag:

```
pnpm run changelog --format markdown 4.0-beta..master | pbcopy
```

This will convert strings like `#123` to `[#123](https://github.com/<owner>/<repo>/issues/123)` (the repository is taken from the `origin` remote), will handle newlines etc.
