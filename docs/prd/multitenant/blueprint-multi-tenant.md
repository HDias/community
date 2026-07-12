# Implementation Blueprint: Multi-Tenant Communities

## 1. Task Analysis

**Objective**: Remove the entire Teams feature (67 files) and build the Communities multi-tenant system from scratch, following `docs/flows/01-multi-tenant.md` and wireframes.

**Not in production** — edit existing migrations in place, no rename migrations.

**Escalation**: Stop and ask on ambiguity.

## 2. Phases

### Phase 1: Remove Teams Feature

Delete all Teams-related files across the stack. Update files that reference teams (User model, bootstrap, middleware registration, auth responses, routes, frontend shell).

**Goal**: App compiles and boots with zero team references. Auth flow works (register → dashboard with no community context).

### Phase 2: Database + Model

Create the Communities data model:

```php
// Edit existing migration or create new: create_communities_table
Schema::create('communities', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('slug')->unique();
    $table->text('description')->nullable();
    $table->string('address')->nullable();
    $table->string('city')->nullable();
    $table->string('state')->nullable();
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();
    $table->softDeletes();
});

Schema::create('community_user', function (Blueprint $table) {
    $table->id();
    $table->foreignId('community_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('role')->default('member');
    $table->timestamp('joined_at')->nullable();
    $table->timestamps();
    $table->unique(['community_id', 'user_id']);
});

// users table: add current_community_id (nullable)
$table->foreignId('current_community_id')->nullable()->constrained('communities')->nullOnDelete();
```

```php
// app/Models/Community.php
#[Fillable(['name', 'slug', 'description', 'address', 'city', 'state', 'created_by'])]
class Community extends Model
{
    use GeneratesUniqueSlugs, HasFactory, SoftDeletes;

    public function members(): BelongsToMany { /* ... */ }
    public function creator(): BelongsTo { /* ... */ }
}
```

```php
// app/Enums/CommunityRole.php
enum CommunityRole: string
{
    case President = 'president';
    case Admin = 'admin';
    case Member = 'member';
}
```

### Phase 3: User Trait + Middleware + Auth Responses

```php
// app/Concerns/HasCommunities.php
trait HasCommunities
{
    public function communities(): BelongsToMany { /* ... */ }
    public function currentCommunity(): BelongsTo { /* nullable */ }
    public function switchCommunity(Community $community): void { /* ... */ }
    public function belongsToCommunity(Community $community): bool { /* ... */ }
}
```

- **Middleware**: `EnsureCommunityMembership` — resolve `{current_community}` route param, verify user membership. Allow null for routes outside community scope.
- **Auth responses**: Login/Register redirect to `/dashboard` if user has a community, or `/communities` (onboarding hub) if none.
- `current_community_id` is nullable — no fallback to a "personal" community.

### Phase 4: Controller + Action + Authorization

```php
// app/Actions/Communities/CreateCommunity.php
public function handle(User $user, array $data): Community
{
    $community = Community::create([...$data, 'created_by' => $user->id]);
    $community->members()->attach($user->id, [
        'role' => CommunityRole::President->value,
        'joined_at' => now(),
    ]);
    $user->switchCommunity($community);
    return $community;
}
```

```php
// app/Http/Controllers/Communities/CommunityController.php
// index: Card Grid data (withCount members, role badges)
// store: CreateCommunity action
// edit/update: location fields
// destroy: soft delete (creator only)
```

**Authorization**: Creator (`created_by`) has full permissions. Simple gate/policy check until administrations feature is built.

### Phase 5: Frontend — Card Grid + Onboarding Hub

- **communities/index.tsx**: Wireframe Option 2 — Card Grid showing communities with role badges, member counts, "Current" marker, and "+ Create" card.
- **communities/onboarding.tsx**: Wireframe Option 5 — Welcome empty state for users with no community. Two paths: "Create" or "Join".
- **community-switcher.tsx**: Sidebar component replacing team-switcher.
- **create-community-modal.tsx**: Form with name, description, address, city, state. Auto-generated slug preview.
- **Types**: `CommunityCard`, `Community`, `CommunityRole` interfaces.

### Phase 6: Routes + Integration + Testing

```php
// routes/web.php
Route::middleware('auth')->group(function () {
    // Community management (no community prefix needed)
    Route::resource('communities', CommunityController::class);
    Route::post('communities/{community}/switch', [CommunityController::class, 'switch']);

    // Routes scoped to current community
    Route::prefix('{current_community}')->middleware('community.member')->group(function () {
        Route::get('/dashboard', DashboardController::class);
        Route::resource('members', MemberController::class);
        // ... other community-scoped routes
    });
});
```

## 3. Test Strategy

### Phase 1 (Removal)

- [ ] App boots without errors
- [ ] Auth tests pass (register, login) with team logic removed

### Phase 2 (Model)

- [ ] Community factory creates valid records
- [ ] Slug is auto-generated and unique (including soft-deleted)
- [ ] community_user pivot stores role and joined_at

### Phase 3 (Trait + Middleware)

- [ ] User can belong to multiple communities
- [ ] switchCommunity updates current_community_id
- [ ] Middleware blocks non-members from community routes
- [ ] Middleware allows null community for global routes

### Phase 4 (Controller)

- [ ] CreateCommunity attaches creator as President
- [ ] Index returns communities with member counts and roles
- [ ] Only creator can delete community
- [ ] Store/update validates location fields

### Phase 5 (Frontend)

- [ ] Card Grid renders communities with correct data
- [ ] Onboarding Hub shows for users with no community
- [ ] Create modal submits and redirects to new community

### Phase 6 (Integration)

- [ ] Full registration → onboarding → create community → dashboard flow works
- [ ] Community switching works from sidebar
- [ ] `npm run build` succeeds
- [ ] `php artisan test --compact` all green
- [ ] `vendor/bin/pint --dirty --format agent` clean

## 4. Files to Remove (Teams Feature — 67 files)

### Backend (delete entirely)

- `app/Models/Team.php`
- `app/Models/TeamInvitation.php`
- `app/Models/Membership.php`
- `app/Concerns/HasTeams.php`
- `app/Concerns/GeneratesUniqueTeamSlugs.php`
- `app/Actions/Teams/CreateTeam.php`
- `app/Http/Controllers/Teams/TeamController.php`
- `app/Http/Controllers/Teams/TeamInvitationController.php`
- `app/Http/Controllers/Teams/TeamMemberController.php`
- `app/Http/Requests/Teams/SaveTeamRequest.php`
- `app/Http/Requests/Teams/DeleteTeamRequest.php`
- `app/Http/Requests/Teams/UpdateTeamMemberRequest.php`
- `app/Http/Requests/Teams/RespondToTeamInvitationRequest.php`
- `app/Http/Requests/Teams/CreateTeamInvitationRequest.php`
- `app/Policies/TeamPolicy.php`
- `app/Http/Middleware/EnsureTeamMembership.php`
- `app/Http/Middleware/SetTeamUrlDefaults.php`
- `app/Enums/TeamPermission.php`
- `app/Enums/TeamRole.php`
- `app/Data/TeamPermissions.php`
- `app/Data/UserTeam.php`
- `app/Rules/ValidTeamInvitation.php`
- `app/Rules/UniqueTeamInvitation.php`
- `app/Rules/TeamName.php`
- `app/Notifications/Teams/TeamInvitation.php`
- `app/Http/Responses/Concerns/RedirectsToCurrentTeam.php`
- `database/factories/TeamFactory.php`
- `database/factories/TeamInvitationFactory.php`

### Migrations (delete and replace)

- `database/migrations/2026_01_27_000001_create_teams_table.php`
- `database/migrations/2026_01_27_000002_add_current_team_id_to_users_table.php`

### Tests (delete)

- `tests/Feature/Teams/TeamTest.php`
- `tests/Feature/Teams/TeamMemberTest.php`
- `tests/Feature/Teams/TeamInvitationTest.php`
- `tests/Feature/Teams/PruneExpiredTeamInvitationsTest.php`

### Frontend (delete)

- `resources/js/pages/teams/index.tsx`
- `resources/js/pages/teams/edit.tsx`
- `resources/js/components/create-team-modal.tsx`
- `resources/js/components/leave-team-modal.tsx`
- `resources/js/components/delete-team-modal.tsx`
- `resources/js/components/team-switcher.tsx`
- `resources/js/components/team-invitation-alert.tsx`
- `resources/js/components/invite-member-modal.tsx`
- `resources/js/components/remove-member-modal.tsx`
- `resources/js/components/cancel-invitation-modal.tsx`
- `resources/js/components/pending-invitations-modal.tsx`
- `resources/js/types/teams.ts`
- `resources/js/routes/teams/index.ts`
- `resources/js/routes/teams/members/index.ts`
- `resources/js/routes/teams/invitations/index.ts`
- `resources/js/actions/App/Http/Controllers/Teams/TeamController.ts`
- `resources/js/actions/App/Http/Controllers/Teams/TeamMemberController.ts`
- `resources/js/actions/App/Http/Controllers/Teams/TeamInvitationController.ts`
- `resources/js/actions/App/Http/Controllers/Teams/index.ts`

### Files to Edit (remove team references)

- `app/Models/User.php` — remove `HasTeams` trait, `current_team_id` references
- `app/Http/Middleware/HandleInertiaRequests.php` — remove team shared props
- `app/Http/Responses/LoginResponse.php` — remove team redirect logic
- `app/Http/Responses/RegisterResponse.php` — remove team redirect logic
- `app/Http/Responses/VerifyEmailResponse.php` — remove team redirect logic
- `app/Http/Responses/TwoFactorLoginResponse.php` — remove team redirect logic
- `app/Http/Responses/PasskeyLoginResponse.php` — remove team redirect logic
- `app/Actions/Fortify/CreateNewUser.php` — remove personal team creation
- `app/Providers/FortifyServiceProvider.php` — remove team references
- `bootstrap/app.php` — remove team middleware registration
- `routes/settings.php` — remove team routes
- `routes/web.php` — remove `{current_team}` prefix, invitation routes
- `routes/console.php` — remove invitation prune command
- `resources/js/components/app-sidebar.tsx` — remove TeamSwitcher
- `resources/js/components/app-header.tsx` — remove TeamSwitcher
- `resources/js/components/user-info.tsx` — remove team prop
- `resources/js/pages/auth/login.tsx` — remove invitation context
- `resources/js/pages/auth/register.tsx` — remove invitation context
- `resources/js/types/global.d.ts` — remove Team type imports
- `resources/js/types/index.ts` — remove teams re-export
- `tests/Feature/Auth/RegistrationTest.php` — remove team assertions
- `tests/Feature/Auth/AuthenticationTest.php` — remove team assertions
- `tests/Feature/Auth/EmailVerificationTest.php` — remove team assertions
