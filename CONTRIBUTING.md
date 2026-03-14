# Contributing

Contributions are welcome and accepted via pull requests.
Please review these guidelines before submitting any pull requests.

## Process

1. Fork the project
2. Create a new branch from `main`
3. Code, test, commit and push
4. Open a pull request detailing your changes

## Guidelines

- Please ensure all quality checks pass by running `composer run check`
- Write commit messages as short imperative sentences, no conventional commit prefixes (e.g., `Add DNS batch support`, `Fix DNSSEC parameter bug`)
- Send a coherent commit history, making sure each individual commit in your pull request is meaningful
- You may need to [rebase](https://git-scm.com/book/en/v2/Git-Branching-Rebasing) to avoid merge conflicts
- Please remember that we follow [SemVer](https://semver.org/)

## Setup

Clone your fork, then install the dev dependencies:

```bash
composer install
```

## Code Style

Check code style:

```bash
composer run pint
```

Fix auto-fixable issues:

```bash
composer run fix
```

## Static Analysis

Run PHPStan (level 8):

```bash
composer run phpstan
```

## Tests

Run all tests:

```bash
composer run test
```

Run a specific test file:

```bash
vendor/bin/pest tests/Unit/Api/DnsTest.php
```

## All Checks

Run everything at once (style, static analysis, rector, tests):

```bash
composer run check
```

This is what CI runs — make sure it passes before opening a PR.
