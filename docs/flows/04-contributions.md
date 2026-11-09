# Contributions and Donations

Mandatory monthly contribution with a fixed amount for all members.
Amount can be changed by meeting or community decision.
Donations are free, with no mandatory requirement.

The treasurer only registers members who paid. At the end of each month, an async job
automatically generates a default report by identifying members without a payment record.

A registered monthly payment can be **reversed** (voided) with an audit trail; the month
then becomes pending again and can be registered afresh.

## Data Model

```mermaid
erDiagram
    contribution_settings {
        bigint id PK
        bigint community_id FK
        decimal monthly_amount
        date effective_from
        timestamp created_at
        timestamp updated_at
    }
    contributions {
        bigint id PK
        bigint community_id FK
        bigint user_id FK "nullable - null for non-member donors"
        date reference_month "YYYY-MM"
        date monthly_key "nullable - set only for monthly payments"
        decimal amount
        bigint registered_by FK
        varchar type "monthly or donation"
        varchar donor_type "nullable - member, individual or organization"
        varchar donor_name "nullable"
        varchar donor_document "nullable - CPF or CNPJ digits"
        text notes "nullable"
        timestamp reversed_at "nullable"
        bigint reversed_by FK "nullable"
        text reversal_reason "nullable"
        timestamp created_at
        timestamp updated_at
    }
    contribution_reports {
        bigint id PK
        bigint community_id FK
        date reference_month "YYYY-MM"
        int total_members
        int total_paid
        int total_defaulting
        decimal total_collected
        timestamp generated_at
        timestamp created_at
        timestamp updated_at
    }
    communities ||--o{ contribution_settings : "configures"
    communities ||--o{ contributions : "receives"
    communities ||--o{ contribution_reports : "generates"
    users ||--o{ contributions : "pays"
    users ||--o{ contributions : "reverses"
```

Monthly duplicates are blocked by the unique index
`contributions_monthly_unique (community_id, user_id, monthly_key)`. Reversing a payment
clears `monthly_key`, so the same member/month can be registered again while the voided
row stays for audit.

## Flow: Register Monthly Payment

```mermaid
flowchart TD
    A[Treasurer/Admin opens contributions] --> B{Amount configured and already effective?}
    B -->|No| C[Shows warning and disables Register payment]
    B -->|Yes| D[Selects member and reference month]
    D --> E{Month already paid by an active payment?}
    E -->|Yes| F[Displays warning - already registered]
    E -->|No| G[Confirmation dialog]
    G -->|Cancel| D
    G -->|Confirm| H[Registers payment with the effective amount]
    H --> I[Records registered_by and timestamp]
    I --> J[Payment confirmed]
```

The effective amount is the latest `contribution_settings` row whose `effective_from`
has already arrived (today or earlier) and falls within the reference month. Future-dated
settings are ignored until their date arrives; if only future settings exist, registration
is blocked.

## Flow: Reverse Monthly Payment

```mermaid
flowchart TD
    A[Treasurer/Admin clicks Revert on a paid member] --> B[Confirmation dialog]
    B --> C[Enters a required reversal reason]
    C --> D{Already reversed?}
    D -->|Yes| E[Rejects the reversal]
    D -->|No| F[Sets reversed_at and reversed_by]
    F --> G[Stores reversal_reason]
    G --> H[Clears monthly_key]
    H --> I[Month becomes pending again]
    I --> J[Payment no longer counts as paid in index, streaks or reports]
```

Only admins and executives of the community may reverse a payment. The voided row is
kept for audit; it is excluded from the paid status, default streaks and generated report
totals. Donations use the same reversal fields and can be voided from the donations list.

## Flow: Donations Ledger

```mermaid
flowchart TD
    A[Treasurer/Admin opens Donations from the contributions header] --> B[Lists donations of the selected month]
    B --> C{Filter}
    C -->|Month| D[Shows only donations of that reference month]
    C -->|All months| E[Shows donations from every month]
    C -->|Search| F[Filters by member name, donor name or CPF/CNPJ]
    D --> G[Rows paginated - 50 per page]
    E --> G
    F --> G
    G --> H{Status}
    H -->|Active| I[Offers Revert with a required reason]
    H -->|Reversed| J[Shows the Reversed badge and keeps the row for audit]
    I --> K[Reversal fields recorded, badge changes to Reversed]
```

The donations list only shows rows with `type = donation`; monthly payments stay in the
contributions overview. The month defaults to the current month when the page is opened
from the contributions header, and the "All months" button removes the month filter.

## Flow: View Payment History

```mermaid
flowchart TD
    A[Treasurer/Admin clicks History on a row] --> B{Any history for the month?}
    B -->|No| C[Button disabled]
    B -->|Yes| D[Modal lists each registered row in chronological order]
    D --> E[Shows the amount, who registered it and when]
    D --> F[Shows each reversal with who reversed it, when and the reason]
```

History is derived from the existing `contributions` rows for the selected month, including
reversed ones. A month paid, reversed and paid again therefore shows two entries: the voided
row (with its reversal metadata) and the active replacement.

## Flow: Monthly Report Generation (Async Job)

```mermaid
flowchart TD
    A[End of month - scheduled job runs] --> B[For each community with active members]
    B --> C[Gets all members from community_user]
    C --> D[Gets active monthly contributions for current month]
    D --> E[Members without an active payment = defaulting]
    E --> F[Generates contribution_report]
    F --> G[Report available for Treasurer/Admin]
```

Reversed payments are ignored when computing `total_paid` and `total_collected`.

## Flow: View Payment Defaults

```mermaid
flowchart TD
    A[Treasurer/Admin accesses reports] --> B[Views auto-generated monthly report]
    B --> C[Lists defaulting members for selected month]
    C --> D[Shows accumulated defaults per member]
    D --> E[Highlights members with 3+ months overdue]
```

Reversed months count as owed again in the accumulated default streak.

## Flow: Change Contribution Amount

```mermaid
flowchart TD
    A[Admin accesses contribution settings] --> B[Sets new monthly amount]
    B --> C[Sets effective date - effective_from]
    C --> D{Effective date already arrived?}
    D -->|No| E[Stored, but not applied until that date]
    D -->|Yes| F[Applied from that date onward]
    F --> G[History of previous amounts preserved]
```

The latest already-effective setting wins. A future setting does not override the
currently active amount until its date arrives.

## Flow: Register Donation

```mermaid
flowchart TD
    A[Treasurer/Admin opens Register donation] --> B{Donor type}
    B -->|Member| C[Selects a community member]
    B -->|Individual| D[Enters name and CPF]
    B -->|Legal entity| E[Enters corporate name and CNPJ]
    C --> F[Enters amount and optional notes]
    D --> F
    E --> F
    F --> G[Registers with type = donation]
```

Donations are not tied to the monthly amount and are never included in the default streak.
They are registered from the donations ledger, which also lists, filters and reverses them
(see "Flow: Donations Ledger").

## Flow: Find a Member in the Contributions List

```mermaid
flowchart TD
    A[Treasurer/Admin opens contributions] --> B[Types a name or CPF in the search field]
    B --> C[Debounced request after 300ms]
    C --> D[Server filters members by name or CPF]
    D --> E[Results paginated - 50 per page by default]
    E --> F[Paid/Pending status per member for the selected month]
```

## Flow: Member Views Own Payment History (planned)

Not implemented yet. Planned: the member sees every month since joining with paid/pending
status and the amount/date of paid months.
