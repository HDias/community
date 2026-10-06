# Community Management Platform

A web application developed as a volunteer project to support the administrative management of the Boa Esperança Quilombola Community, located in Mateiros, Tocantins, Brazil.

The goal is to replace manual administrative processes with a simple, centralized system for managing community members, meetings, contributions, and other day-to-day activities.

About the Project

The Boa Esperança community needs to keep track of its members, community meetings, contributions, and administrative records.

This application is being developed to centralize this information and make these processes easier to manage, while keeping the system simple and accessible for the people responsible for the community's administration.

This is an ongoing project and is being developed voluntarily.

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

## Architecture

See [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) for the full architecture overview, domain model, folder structure, and Mermaid diagrams.

## Building for Production

```bash
npm run build
```
