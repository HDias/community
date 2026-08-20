# Tech Debt

Tracked issues that are known trade-offs or gaps, not yet worth blocking a merge but worth revisiting.

## Members feature (`feat/members`)

### 1. `MemberRegistered` notification is not queued

- **Where:** `app/Notifications/MemberRegistered.php`
- **Issue:** Sends mail synchronously during `RegisterMember::handle()`, blocking the HTTP response on an external mail send.
- **Fix:** Implement `ShouldQueue` + `Queueable` on the notification. The notification is already dispatched after the `DB::transaction()` closure returns (`app/Actions/Members/RegisterMember.php:53`), so no `afterCommit()` handling is needed.

### 2. Temporary password emailed in plaintext

- **Where:** `app/Actions/Members/RegisterMember.php`, `app/Notifications/MemberRegistered.php`
- **Issue:** A 12-character password is generated and included as plaintext in the welcome email. Common pattern for admin-created accounts, but worth confirming this is the intended onboarding flow rather than using a password-reset/invitation-token link.

### 3. CPF stored unencrypted

- **Where:** `database/migrations/2026_07_18_020817_create_profiles_table.php`, `app/Models/Profile.php`
- **Issue:** `cpf` (Brazil's national ID, comparable sensitivity to a US SSN) is stored as plain text with a unique index. Laravel's standard `encrypted` cast is non-deterministic and would break `unique:profiles,cpf` validation and `WHERE cpf = ?` lookups, so encrypting it would require a deterministic/blind-index approach. Flagging as a design decision to make deliberately, not a quick fix.
