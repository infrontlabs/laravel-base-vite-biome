# Laravel + React + Biome Starter

An opinionated baseline for building Laravel apps with an Inertia + React frontend, where **Biome** replaces the usual ESLint + Prettier toolchain.

Forked from [`laravel/react-starter-kit`](https://github.com/laravel/react-starter-kit). The PHP, Inertia, Vite, and Fortify pieces match upstream; the JS/TS tooling is the main divergence.

## Stack

- **Laravel 13** (PHP 8.3+) with **Laravel Fortify** for auth (login, registration, password reset, email verification, 2FA)
- **Inertia.js 3** + **React 19** (with the React Compiler enabled via Babel)
- **TypeScript 5** (strict mode)
- **Vite 8** with the Inertia, Tailwind, React, and **Wayfinder** plugins
- **Tailwind CSS 4** + **shadcn/ui** (`radix-sera` style, `olive` base color, `hugeicons` icon library)
- **Biome 2** for JS/TS lint + format + import sorting (replaces ESLint, Prettier, and `prettier-plugin-tailwindcss`)
- **Pest 4** for tests, **Laravel Pint** for PHP formatting
- **SQLite** by default (swap via `DB_CONNECTION` in `.env`)

## Requirements

- PHP `^8.3` and Composer
- Node `>=22` and pnpm (the repo uses `pnpm-lock.yaml`)
- A terminal that can keep multiple processes running (the dev script multiplexes server, queue, log tail, and Vite)

## Getting started

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
pnpm install
pnpm run build
```

Or run the bundled setup script:

```bash
composer setup
```

## Development

Run everything (PHP server, queue worker, log tail, Vite) in one command:

```bash
composer dev
```

Individual processes:

```bash
php artisan serve            # backend at http://localhost:8000
php artisan queue:listen     # queue worker
php artisan pail             # tail logs
pnpm run dev                 # Vite dev server
```

## Quality gates

```bash
composer ci:check            # full gate: biome check + tsc + pint --test + pest
composer test                # pint --test + php artisan test
pnpm run lint                # biome check --write . (autofix)
pnpm run lint:check          # biome check . (no writes)
pnpm run format              # biome format --write .
pnpm run types:check         # tsc --noEmit
composer lint                # pint --parallel (PHP autofix)
./vendor/bin/pest --filter "test name"
```

## Conventions

### Biome

- Config in [`biome.json`](./biome.json), scoped to `resources/**`. Root config files (`composer.json`, `package.json`, `components.json`, etc.) are intentionally out of scope.
- Excluded paths: `resources/js/components/ui/**` (vendored shadcn), `resources/views/mail/**`, and anything in `.gitignore` (which already covers the wayfinder-generated `routes/`, `actions/`, `wayfinder/` trees).
- Tailwind classes inside `clsx`, `cn`, and `cva` are auto-sorted via `useSortedClasses`.
- Formatter: 4-space indent, 80-column width, single quotes, semicolons. YAML and Markdown pass through untouched.

### Inertia + Wayfinder

- Pages live in `resources/js/pages/` and are rendered from controllers via `Inertia::render('page/name', $props)`.
- Layouts are applied centrally in `resources/js/app.tsx` based on the page-name prefix (`auth/*`, `settings/*`, etc.).
- The `wayfinder` Vite plugin generates typed route helpers (`resources/js/routes/`), controller-action wrappers (`resources/js/actions/`), and runtime utilities (`resources/js/wayfinder/`) from the PHP routes. **These directories are gitignored and regenerated on the fly — never edit them by hand.**
- Shared Inertia props (`auth.user`, `name`, `sidebarOpen`) are typed via `InertiaConfig.sharedPageProps` in `resources/js/types/global.d.ts`. Extend that interface when you add new shared props.

### Auth

Fortify wires its auth views to Inertia pages in [`app/Providers/FortifyServiceProvider.php`](./app/Providers/FortifyServiceProvider.php). Most auth routes are registered by Fortify; only app-level routes live in `routes/web.php` and `routes/settings.php`.

## What's intentionally not included

- No Tailwind plugin for Prettier (Biome handles class sorting natively)
- No ESLint plugins for React/import order (Biome's recommended ruleset + organize-imports cover the same ground)
- No padding-line-between-statements enforcement (Biome has no equivalent of ESLint's `@stylistic` rule)
