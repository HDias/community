# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.2.0] - 2025-07-15

### Added

- Role-based admin access for administration members
- `has_admin_access` flag on positions (defaults to true) to control who sees admin menus
- Configurable position-based authorization (replaces admin-only model)
- Auto-assign president to new administrations on creation
- Required end date when creating new administrations
- Searchable member assignment with debounced API search
- User's current position displayed in sidebar dropdown menu
- Member email shown in administration member lists
- Confirmation dialog on "End administration" action
- Positions and administrations management feature

### Fixed

- PHPStan and ESLint type errors for CI compliance
- ESLint errors in administration pages
- React set-state-in-effect warning in data fetching hook

### Changed

- Administration members with admin-access positions can now manage communities, positions, and administrations
- Sidebar admin menus visibility driven by position flag instead of `is_admin` only
- Community create/delete restricted to system admin only
- "Save dates" no longer ends the administration period (use "End administration" button)
- CI lint workflow now uses PHP 8.5
- CI test matrix narrowed to PHP 8.4 and 8.5 (dropped 8.3)
- Removed stale `workos` branch from CI triggers

## [0.1.0] - 2025-07-13

### Added

- Multi-tenant communities feature with full CRUD (create, edit, delete, switch)
- Community model with auto-generated unique slugs (including soft-deleted records)
- `HasCommunities` trait on User for community membership, switching, and relationship queries
- `CommunityRole` enum (President, Admin, Member)
- `CreateCommunity` action attaches creator as President automatically
- `CommunityPolicy` with creator-based authorization for update/delete
- Admin-only community creation gated by `is_admin` flag on users
- Split View UI for communities (list on left, edit/create form on right)
- Onboarding Hub for users with no communities (with create form for admins)
- BrasilAPI integration for Brazilian states and cities selection
- Backend proxy for BrasilAPI with 24-hour cache to avoid CORS issues
- `LocationFields` reusable component with cascading state/city selects
- `EnsureCommunityMembership` middleware for future community-scoped routes
- Communities link in sidebar navigation
- Community factory and seeder with admin user (`admin@example.com`)
- 18 feature tests covering model, controller, and authorization

### Removed

- Teams feature (models, controllers, migrations, middleware, frontend, tests — 67 files)
- Personal team auto-creation on user registration
- Team invitation system
- Team-based authentication redirects
