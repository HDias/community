# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Contributions overview page with per-member Paid/Pending status and a sidebar entry
- Member search by name or CPF in the contributions list, debounced and paginated
- Confirmation dialog before registering a monthly payment
- Donation registration screen with member, individual (CPF) and legal entity (CNPJ) donors
- Audited reversal (void) of monthly payments with a required reason, recording who reversed it and when
- Donations ledger page with month filter, debounced search (member name, donor name, CPF/CNPJ) and pagination
- Audited reversal of donations, listed with an Active/Reversed status badge
- Payment history column and modal on both lists, showing the amount registered, who registered and reversed each row, when and why
- CNPJ input mask and validation rule
- `PAGINATION_PER_PAGE` configuration, defaulting to 50 records per page

### Changed

- Monthly amounts resolve from the latest setting whose `effective_from` has already arrived; future-dated settings stay inactive until that date
- Reversed payments no longer count as paid in the member list, default streaks or generated reports, and free the month for re-registration
- The contributions header now opens the donations ledger; the donation registration action moved to that page

### Fixed

- Payment registration blocked when the monthly amount was configured with a mid-month effective date
- Registering a payment redirecting to a non-existent contributions page (404)
- Contribution sub-pages missing breadcrumbs back to the overview

## [0.3.0] - 2026-08-20

### Added

- Vitest test harness for the TypeScript front end, with `npm test`, `npm run test:watch` and `npm run test:coverage`
- Unit tests for the CPF and phone input masks and the `cn`/`toUrl` helpers
- Unit tests for the state/city lookup, initials and clipboard hooks
- Integration tests for the member search combobox
- Integration tests for the address/state/city fields, and unit tests for the current-url helpers
- Integration tests for the CPF and phone input components, covering the raw-to-masked rendering the edit member page relies on
- Integration tests for passkey registration, including the user-agent precedence that names a new passkey
- Integration tests for the members index page, covering the paginated/empty prop shapes and the community switcher
- Integration tests for the community onboarding page, covering the create permission gate and the form's validation contract
- Integration tests for the create member page, pinning the fourteen field names `StoreMemberRequest` validates and the state-to-city dependency
- Integration tests for the edit member page, covering the pre-populated fields, the client-side masking of the raw CPF and phone, and the saved state seeding the city list
- Server-side prop assertions on the member and community pages, so a renamed Inertia prop fails a PHP test instead of only a stale front-end mock
- CI now runs the front-end type check and test suite, and `composer ci:check` runs `npm test` alongside the other gates

### Fixed

- CI type check and lint steps failing because the gitignored Wayfinder `@/actions` and `@/routes` modules were only generated later by the asset build
- Linter workflow running Pint, Prettier and ESLint in write mode and discarding the result, so style drift and lint errors could never fail a build
- Member search firing a request after the component unmounted, because its debounce timer was never cleared
- Dashboard link on the welcome page always pointing at `/` because it read a `currentTeam` prop the server never shared

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
