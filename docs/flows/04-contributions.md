# Contributions and Donations

Mandatory monthly contribution with a fixed amount for all members.
Amount can be changed by meeting or community decision.
Donations are free, with no mandatory requirement.

The treasurer only registers members who paid. At the end of each month, an async job
automatically generates a default report by identifying members without a payment record.

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
        bigint user_id FK
        date reference_month "YYYY-MM"
        decimal amount
        bigint registered_by FK
        varchar type "monthly or donation"
        text notes "nullable"
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
```

## Flow: Register Monthly Payment

```mermaid
flowchart TD
    A[Treasurer/Admin accesses contributions] --> B[Selects member]
    B --> C[Selects reference month]
    C --> D{Month already paid?}
    D -->|Yes| E[Displays warning - already registered]
    D -->|No| F[Registers payment with current amount]
    F --> G[Records registered_by and timestamp]
    G --> H[Payment confirmed]
```

## Flow: Monthly Report Generation (Async Job)

```mermaid
flowchart TD
    A[End of month - scheduled job runs] --> B[For each community with active members]
    B --> C[Gets all members from community_user]
    C --> D[Gets all contributions for current month]
    D --> E[Members without payment record = defaulting]
    E --> F[Generates contribution_report]
    F --> G[Report available for Treasurer/Admin]
```

## Flow: View Payment Defaults

```mermaid
flowchart TD
    A[Treasurer/Admin accesses reports] --> B[Views auto-generated monthly report]
    B --> C[Lists defaulting members for selected month]
    C --> D[Shows accumulated defaults per member]
    D --> E[Highlights members with 3+ months overdue]
```

## Flow: Change Contribution Amount

```mermaid
flowchart TD
    A[Admin accesses contribution settings] --> B[Sets new monthly amount]
    B --> C[Sets effective date - effective_from]
    C --> D[New amount applied from defined month onward]
    D --> E[History of previous amounts preserved]
```

## Flow: Register Donation

```mermaid
flowchart TD
    A[Treasurer/Admin accesses donations] --> B[Selects member or registers anonymous donation]
    B --> C[Enters free amount]
    C --> D[Adds optional notes]
    D --> E[Registers with type = donation]
```

## Flow: Member Views Own Payment History

```mermaid
flowchart TD
    A[Member accesses payment history] --> B[Lists all months since joining]
    B --> C[Each month shows paid or pending status]
    C --> D[Paid months show amount and date]
    D --> E[Pending months highlighted]
```
