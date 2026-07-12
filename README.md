# Community

A Laravel application for managing communities with multi-tenant architecture. Built with Laravel, Inertia.js, React, and Tailwind CSS.

## Requirements

- PHP 8.5
- Composer
- Node.js & npm
- SQLite (default) or MySQL/PostgreSQL

## Setup

```bash
composer run setup
```

This will:

1. Install PHP dependencies
2. Create `.env` from `.env.example`
3. Generate the application key
4. Run database migrations

Then install frontend dependencies:

```bash
npm install
```

## Running the App

```bash
composer run dev
```

This starts both the PHP server and Vite dev server together.

Alternatively, run them separately:

```bash
# Terminal 1 - PHP server
php artisan serve

# Terminal 2 - Vite (hot reload)
npm run dev
```

The app will be available at `http://localhost:8000`.

## Database

Reset the database (drop all tables and re-run migrations):

```bash
php artisan migrate:fresh
```

With seeders:

```bash
php artisan migrate:fresh --seed
```

## Testing

```bash
php artisan test --compact
```

## Code Quality

```bash
# PHP formatting
vendor/bin/pint

# Frontend formatting
npm run format

# Static analysis
vendor/bin/phpstan analyse
```

## Building for Production

```bash
npm run build
```
