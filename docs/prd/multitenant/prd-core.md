# AI Operational Guide: Multi-Tenant Communities

## 0. LLM Context Files Reference

> **Note**: This PRD only contains information NOT in existing LLM context files.
>
> **Existing files**: `AGENTS.md` (Laravel conventions, testing, Inertia patterns)
> **Already covered**: PHP standards, Pest testing, Inertia+React patterns, Wayfinder, Pint formatting

## 1. Guidelines

### 1.1 Project Overview

This application manages communities with multi-tenant architecture. Each user can belong to multiple communities and has a `current_community_id` for context switching. Routes are scoped under `{current_community}/`.

**Not in production** — existing migrations can be edited in place (no rename migrations needed).

### 1.2 Domain Model (Source of truth: `docs/flows/01-multi-tenant.md`)

- **communities** table: `id`, `name`, `slug` (unique), `description`, `address`, `city`, `state`, `created_by` (FK → users), `timestamps`
- **community_user** pivot: `id`, `community_id`, `user_id`, `role`, `joined_at`, `timestamps`
- A user can belong to multiple communities
- Each community is independent — no "personal" communities

### 1.3 Key Decisions

| Decision                        | Choice                                                                                    | Rationale                                                  |
| ------------------------------- | ----------------------------------------------------------------------------------------- | ---------------------------------------------------------- |
| No personal teams/communities   | Users start with no community                                                             | Wireframe Option 5 handles empty state                     |
| `current_community_id` nullable | Yes                                                                                       | New users have none until admin adds them                  |
| Role on pivot                   | Keep `role` column on `community_user`                                                    | Wireframes show role badges (President, Treasurer, Member) |
| Authorization model             | App-level admin (`is_admin` on users) creates communities; `created_by` user manages them | Only admins can create; creator has full permissions       |
| Community creation              | Only app-level admins can create communities                                              | Regular users cannot self-create communities               |
| Member management               | Admin adds users to communities directly                                                  | No self-join, no invitations, no join requests             |
| Registration flow               | Register → Onboarding Hub (wait for admin to add you)                                     | No community auto-created, no create button for users      |
| Soft deletes                    | Keep on communities                                                                       | Slug uniqueness must check trashed records                 |

### 1.4 Task-Specific Guidelines

- **Naming**: Use "Community" everywhere — model, routes, controllers, frontend.
- **Data pipeline**: Community data flows through shared Inertia props for sidebar switching. Page-specific data (member counts) uses separate queries.
- **Model attributes**: Community model uses `#[Fillable]` PHP attribute.
- **TDD**: Write failing tests before implementation code.
- **Approach**: Remove the entire Teams feature first (67 files), then build Communities from scratch.

## 2. Workflow

Task Lifecycle: Blueprint > Approval > Execution > Analysis > Learning

### Document Update Protocol

When modifying PRD documents:

1. Announce the change clearly
2. Present the complete updated file (clean, no annotations)

## 3. Output Standard

- All tests pass (`php artisan test --compact`)
- Pint formatting applied (`vendor/bin/pint --dirty --format agent`)
- Frontend builds without errors (`npm run build`)
- Wireframe Option 2 (Card Grid) implemented on communities index page
- Wireframe Option 5 (Onboarding Hub) implemented for users with no community

## 4. Lessons Learned

[Populated during implementation]
