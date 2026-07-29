# WireKit

<p>
    <a href="https://github.com/dsolodev/wirekit/actions"><img src="https://github.com/dsolodev/wirekit/actions/workflows/tests.yml/badge.svg" alt="Build Status"></a>
    <a href="https://packagist.org/packages/dsolodev/wirekit"><img src="https://img.shields.io/packagist/dt/dsolodev/wirekit" alt="Total Downloads"></a>
    <a href="https://packagist.org/packages/dsolodev/wirekit"><img src="https://img.shields.io/packagist/v/dsolodev/wirekit" alt="Latest Stable Version"></a>
    <a href="https://packagist.org/packages/dsolodev/wirekit"><img src="https://img.shields.io/packagist/l/dsolodev/wirekit" alt="License"></a>
</p>

**WireKit** is an opinionated starter kit for [Laravel](https://laravel.com) with [Filament](https://filamentphp.com/)
as the admin panel.

## ✨ Features

- ✅ **Filament 5** admin panel pre-configured
- ✅ **Rector**, **Pint**, **Prettier** for automated code quality
- ✅ **PHPStan Level Max** (maximum strictness)
- ✅ **100% Type Coverage** with Pest
- ✅ **Filament Shield** roles and permissions, with the first user as super admin
- ✅ **One-command install** that creates the database and seeds an administrator

## 🚀 Installation

### Starting a new project — the whole sequence

```bash
laravel new my-app --using=dsolodev/wirekit   # runs wirekit:install for you
cd my-app
npm install && npm run build
composer dev                                   # http://localhost:8000/admin

gh repo create my-app --private --source=. --push
gh secret set FILAMENT_COMPOSER_USERNAME    --body "your@email.com"
gh secret set FILAMENT_COMPOSER_LICENSE_KEY --body "your-license-key"
```

Those last two lines are the step that is easy to forget and the reason CI goes red on a brand new repo. See
[The Filament licence](#-the-filament-licence-read-this-when-something-401s) for why.

Prerequisites: PHP 8.5, a running MySQL, Node, and the Filament licence already configured globally on this
machine.

### Quick Start with Laravel Installer

You can use the [Laravel Installer](https://laravel.com/docs#installing-php) to install this starter kit.

```bash
laravel new my-app --using=dsolodev/wirekit
cd my-app
```

### Alternative: Using Composer

```bash
composer create-project dsolodev/wirekit --prefer-dist my-app
cd my-app
```

Both commands run `php artisan wirekit:install`, which:

1. Names the app after the project directory (`my-app` becomes `My App`).
2. Creates the MySQL database named after the project directory (`my-app` becomes `my_app`).
3. Generates the application key and runs the migrations.
4. Generates Shield's policies and permissions, and grants them to the `super_admin` role.
5. Seeds the first administrator, who is promoted to `super_admin`.

You can then build the assets and start the server:

```bash
npm install && npm run build
composer dev
```

Sign in at `/admin` with:

| Email                 | Password   |
|-----------------------|------------|
| `admin@my-app.test`   | `password` |

That user is created first, so it receives the `super_admin` role automatically.

The email follows the project name, so a project in `blog` seeds `admin@blog.test`. **Change this password before
deploying anywhere.**

### Database requirements

The installer expects a reachable MySQL server and connects with the credentials in your `.env` file, defaulting to
`root` with no password on `127.0.0.1:3306`. Edit `DB_USERNAME` / `DB_PASSWORD` in `.env` before installing if your
server differs.

If the installer cannot reach MySQL, it says so and stops without touching anything else. Create the database yourself
and finish the install with:

```bash
php artisan migrate --seed
```

To use a different driver, set `DB_CONNECTION` in `.env` before installing. Only MySQL databases are created
automatically; other drivers are migrated as-is.

## 🔑 The Filament licence (read this when something 401s)

`filament/blueprint` sits in `require-dev` and is a **paid** package served from `packages.filamentphp.com`, which
answers `HTTP 401` to anyone unauthenticated. Two places need the licence key, and they are configured separately.

### On a new machine — once, ever

```bash
composer config --global --auth http-basic.packages.filamentphp.com "your@email.com" "your-license-key"
```

This writes `~/.composer/auth.json`, outside any project, so it covers every project at once and can never be
committed by accident. Verify it took:

```bash
composer config --global --list | grep filamentphp
```

The key itself is in your Filament account at <https://filamentphp.com/dashboard>.

### In every new repo — once per project

GitHub Actions cannot see `~/.composer/auth.json`, and personal accounts have no account-wide Actions secrets, so
**each repo built from this kit needs its own two secrets.** Fastest way, from inside the fresh project:

```bash
gh secret set FILAMENT_COMPOSER_USERNAME    --body "your@email.com"
gh secret set FILAMENT_COMPOSER_LICENSE_KEY --body "your-license-key"
```

Or by hand: **Settings → Secrets and variables → Actions → New repository secret**.

`.github/workflows/tests.yml` folds them into the `COMPOSER_AUTH` environment variable, which Composer reads in
place of an `auth.json`. Nothing is written to disk, so no later step can print the key, and GitHub masks both
values in the logs.

**Forget this step and CI fails on `composer install` with a 401 on `filament/blueprint`** — that is the symptom
to recognise, and this section is the fix.

## 🛠️ Pre-configured Development Tools

- **[Pint](https://laravel.com/docs/pint)** - Code style fixer (PSR-12 + Laravel)
- **[Rector](https://getrector.com/)** - Automated refactoring
- **[Pest](https://pestphp.com/)** - Testing framework
- **[Prettier](https://prettier.io/)** - JS/CSS formatter
- **[Larastan](https://github.com/larastan/larastan)** - PHPStan for Laravel
- **[Laravel Boost](https://laravel.com/docs/boost)** - Laravel AI Agent Starter Kit

### Available Commands

```bash
# Setup
composer setup                  # Install dependencies, create the database, migrate, seed, and build assets
php artisan wirekit:install     # Re-run the installer on its own (safe to run repeatedly)

# Development
composer dev                    # Start development server with hot reloading, queue worker, and log monitoring

# Code quality
composer lint                   # Auto-fix code style issues and refactoring with Pint, Rector, Prettier
composer test:lint              # Check code style issue and refactoring (dry-run) for CI/CD pipeline

# Testing
composer test:unit              # Run the Pest test suite
composer test:type-coverage     # Check type coverage using Pest
composer test:types             # Run PHPStan analysis at max level
composer test                   # Run everything above: lint, static analysis, type coverage, and tests

# Maintenance
composer update:requirements    # Update all PHP and NPM dependencies to the latest versions
```

> `composer update:requirements` is deliberately a manual command. Bumping every constraint is a decision you make
> before a release, not a side effect of installing a single package.

## ⚙️ Application defaults

`AppServiceProvider` sets a handful of opinionated global defaults. Two of them are worth understanding before
you build on this kit.

### Mass assignment is disabled

```php
Model::unguard();
```

Every write in a Filament panel goes through a schema, and Filament only ever saves the fields declared in that
schema. `$fillable` therefore protects nothing here while costing you a list to maintain on every model, so it is
turned off globally.

**This trade-off stops holding the moment request data reaches a model from anywhere else.** If you add an API
controller, a public-facing form, or anything resembling `Model::create($request->all())`, you no longer have
mass assignment protection: a caller could set `is_active`, `id`, or any other column by adding it to the
payload. In that case either drop `Model::unguard()` and declare `$fillable`, or validate first and pass an
explicit array:

```php
$user->update($request->safe()->only(['name', 'email']));
```

### Strict mode is development-only

```php
Model::shouldBeStrict(! $this->app->isProduction());
```

Strict mode turns lazy loading, reading a missing attribute, and discarding an unfillable attribute into
exceptions. That is exactly what you want while developing and exactly what you do not want in front of real
users, where a single unloaded column would become a 500 instead of a `null`.

This matters for authorization in particular. `User::canAccessPanel()` returns `$this->is_active`, so a user
loaded without that column throws in development (loudly telling you to fix the query) and denies access in
production (failing closed). Both behaviours are correct for their environment.

### The rest

| Default | Effect |
|---|---|
| `Date::use(CarbonImmutable::class)` | Dates are immutable, matching Pint's `date_time_immutable` rule. `$date->addDay()` returns a new instance instead of mutating. |
| `DB::prohibitDestructiveCommands(...)` | `migrate:fresh`, `db:wipe` and friends refuse to run in production. |
| `URL::forceHttps(...)` | Generated URLs use HTTPS in production regardless of what the proxy reports. |
| `Password::defaults(...)` | Minimum 12 characters with mixed case and numbers; also checked against known breaches in production. |

## 🛡️ Roles and permissions

[Filament Shield](https://github.com/bezhanSalleh/filament-shield) provides role-based access control on top of
[spatie/laravel-permission](https://spatie.be/docs/laravel-permission). Roles are managed from the **Roles** resource
inside the panel.

### The first user is the super admin

Somebody has to be able to hand out roles before any roles exist, so `UserObserver` promotes the **first user created**
to the `super_admin` role — whether that user comes from the seeder, from `php artisan make:filament-user`, or from
your own code. Every user created afterwards starts with no roles and must be granted them from the panel.

To promote somebody else later:

```bash
php artisan shield:super-admin
```

### After adding a resource

The panel runs with `->strictAuthorization()`, which means Filament **throws** rather than silently denying when a
model has no policy. Shield writes those policies for you, so after creating a resource run:

```bash
php artisan shield:generate --all
```

This generates the missing policies, creates the matching permissions, and grants them all to `super_admin`. The
installer already does this for you on a fresh install.

If you forget, you will see a clear `LogicException` naming the model and the missing ability — that is strict
authorization doing its job, not a bug.

### Panel access versus permissions

These are two different switches:

| Question | Decided by |
|---|---|
| May this user open the panel at all? | `User::canAccessPanel()`, i.e. the `is_active` column |
| What may they see and do once inside? | Their roles and the Shield generated policies |

A user who is active but holds no roles can sign in and will simply find an empty panel.

## 📋 Logs

[Log Viewer](https://log-viewer.opcodes.io/) is available at `/log-viewer` and linked from the panel sidebar. Access is
gated by the `viewLogViewer` ability defined in `AppServiceProvider`, which allows any active user. Tighten it before
you deploy if not every user should read your logs.

## 🎨 Theming

The panel uses Filament's default stylesheet, and the panel font is set once via `->font('Public Sans')` in
`AdminPanelProvider`.

Filament's stylesheet is compiled ahead of time, without visibility of your code, so Tailwind classes written in
your *own* Filament pages and views are not included in it. If you start writing them, generate a custom theme:

```bash
php artisan make:filament-theme admin
npm run build
```

That command creates the theme, adds it to `vite.config.js`, and registers `->viteTheme()` for you. Until you need
it, the default stylesheet is one less thing to build.

## 📖 Resources

### Official Documentation

- [Laravel Documentation](https://laravel.com/docs)
- [Filament Documentation](https://filamentphp.com/docs)
- [PHPStan Documentation](https://phpstan.org/user-guide/getting-started)
- [Rector Documentation](https://getrector.com/documentation)
- [Pest Documentation](https://pestphp.com/docs)

### Packages Used

- **[laravel/framework](https://github.com/laravel/framework)** - The Laravel Framework
- **[filament/filament](https://github.com/filamentphp/filament)** - Admin panel
- **[bezhansalleh/filament-shield](https://github.com/bezhanSalleh/filament-shield)** - Roles and permissions
- **[spatie/laravel-permission](https://github.com/spatie/laravel-permission)** - Permission storage behind Shield
- **[opcodesio/log-viewer](https://github.com/opcodesio/log-viewer)** - Log viewer
- **[larastan/larastan](https://github.com/larastan/larastan)** - PHPStan for Laravel
- **[pestphp/pest](https://github.com/pestphp/pest)** - Testing framework
- **[driftingly/rector-laravel](https://github.com/driftingly/rector-laravel)** - Automated refactoring for Laravel
- **[laravel/boost](https://laravel.com/docs/boost)** - MCP server and guidelines for AI coding agents (dev)

## 📝 License

WireKit is open-sourced software licensed under the [MIT license](LICENSE).