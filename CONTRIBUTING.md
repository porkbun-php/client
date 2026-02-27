# Contributing

Thanks for considering a contribution! Here's how to get started.

## Setup

```bash
git clone https://github.com/porkbun-php/client.git
cd client
composer install
```

## Development Workflow

Run all quality checks (style, static analysis, tests) before submitting a PR:

```bash
composer run check
```

Fix auto-fixable code style issues:

```bash
composer run fix
```

Run individual checks as needed:

```bash
composer run test         # Pest test suite
composer run pint         # Code style (PSR-12 via Laravel Pint)
composer run phpstan      # Static analysis (level max)
```

## Pull Requests

- Branch from `main`
- Keep changes focused — one feature or fix per PR
- Ensure `composer run check` passes before requesting review
