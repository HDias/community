# Execution Instruction: Multi-Tenant Communities

## 1. Core Context

Reference: `docs/prd/multitenant/prd-core.md`

- Remove the entire Teams feature (67 files) — app is not in production
- Build Communities from scratch following `docs/flows/01-multi-tenant.md`
- `current_community_id` is nullable — new users have no community
- Creator-based authorization until administrations feature is built
- No invitations — admin adds members directly
- TDD: write failing tests first

## 2. Action Plan

Reference: `docs/prd/multitenant/blueprint-multi-tenant.md`

6 phases: Remove Teams → Database+Model → Trait+Middleware+Auth → Controller+Action → Frontend → Routes+Integration

## 3. Relevant Source Files

### Source of Truth

| File                                         | Purpose                               |
| -------------------------------------------- | ------------------------------------- |
| `docs/flows/01-multi-tenant.md`              | Data model and flow specification     |
| `docs/wireframe/1007-communities/index.html` | Card Grid + Onboarding Hub wireframes |
| `docs/wireframe/1007-members/index.html`     | Member registration wireframes        |

### Files to Create

| File                                                       | Purpose                                                          |
| ---------------------------------------------------------- | ---------------------------------------------------------------- |
| `app/Models/Community.php`                                 | Community model with `#[Fillable]`, slug, soft deletes           |
| `app/Models/CommunityMember.php`                           | Pivot model for `community_user`                                 |
| `app/Concerns/HasCommunities.php`                          | User trait: communities(), currentCommunity(), switchCommunity() |
| `app/Concerns/GeneratesUniqueSlugs.php`                    | Slug generation (checks trashed)                                 |
| `app/Enums/CommunityRole.php`                              | President, Admin, Member                                         |
| `app/Actions/Communities/CreateCommunity.php`              | Create + attach creator as President                             |
| `app/Http/Controllers/Communities/CommunityController.php` | CRUD + switch                                                    |
| `app/Http/Requests/Communities/SaveCommunityRequest.php`   | Validation                                                       |
| `app/Http/Middleware/EnsureCommunityMembership.php`        | Route-level membership check                                     |
| `app/Policies/CommunityPolicy.php`                         | Creator-based authorization                                      |
| `database/factories/CommunityFactory.php`                  | Factory with location data                                       |
| `resources/js/pages/communities/index.tsx`                 | Card Grid (wireframe Option 2)                                   |
| `resources/js/pages/communities/onboarding.tsx`            | Empty state (wireframe Option 5)                                 |
| `resources/js/components/community-switcher.tsx`           | Sidebar community picker                                         |
| `resources/js/components/create-community-modal.tsx`       | Create form                                                      |
| `resources/js/types/communities.ts`                        | TypeScript types                                                 |
| `tests/Feature/Communities/CommunityTest.php`              | Feature tests                                                    |

### Files to Edit (after removal)

| File                                            | Change                                        |
| ----------------------------------------------- | --------------------------------------------- |
| `app/Models/User.php`                           | Add `HasCommunities` trait, remove `HasTeams` |
| `app/Http/Middleware/HandleInertiaRequests.php` | Share community props instead of team         |
| `app/Http/Responses/LoginResponse.php`          | Redirect to community or onboarding           |
| `app/Http/Responses/RegisterResponse.php`       | Redirect to onboarding (no auto-create)       |
| `app/Actions/Fortify/CreateNewUser.php`         | Remove personal team creation                 |
| `bootstrap/app.php`                             | Register community middleware                 |
| `routes/web.php`                                | `{current_community}` prefix group            |
| `resources/js/components/app-sidebar.tsx`       | Use CommunitySwitcher                         |

## 4. Execution Directive

Execute the Action Plan step-by-step:

1. **Remove** all 67 Teams files (see blueprint Section 4)
2. **Edit** files that referenced teams to remove dead imports/logic
3. **Verify** app boots: `php artisan route:list`, `npm run build`
4. **Write failing tests** for Community model, factory, slug generation
5. **Implement** Phase 2 (migration, model, enum, factory)
6. **Write failing tests** for HasCommunities trait, middleware, auth responses
7. **Implement** Phase 3 (trait, middleware, auth responses)
8. **Write failing tests** for CreateCommunity action, controller
9. **Implement** Phase 4 (action, controller, policy, request)
10. **Implement** Phase 5 (frontend: types, Card Grid, Onboarding Hub, switcher, modal)
11. **Implement** Phase 6 (routes, integration)
12. **Run** full test suite + pint + build
13. **Update** blueprint checklist (mark completed)
