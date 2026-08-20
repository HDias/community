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

### 4. No test coverage for the TypeScript formatters

- **Where:** `resources/js/components/phone-input.tsx` (`formatPhone`), `resources/js/components/cpf-input.tsx` (`formatCpf`)
- **Issue:** Both are pure functions with real branching (partial input, 8- vs 9-digit subscriber split, already-formatted input, overflow truncation) and no automated tests. The project has no JS test runner at all — no vitest/jest/testing-library, no `npm test` script — so covering them requires adding a dev dependency.
- **Accepted risk:** These are display/input-mask only. A formatting bug is visible on screen and cannot corrupt stored data, because `StoreMemberRequest` and `UpdateMemberRequest` strip `cpf` and `phone` to digits server-side regardless of what the mask produces.
- **Fix:** Add `vitest` as a dev dependency plus an `npm test` script, then unit-test both formatters. Neither needs jsdom. Component-level tests (typing behaviour, table rendering) would additionally need `jsdom` + `@testing-library/react`.
