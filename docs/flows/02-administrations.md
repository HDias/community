# Administrations and Positions

Each community can define its own positions (roles).
Default initial positions: President, Vice-President, Secretary, Treasurer.
An administration has a period (start/end). History is preserved.
A member can hold only one position at a time.

## Implementation Status

> Checked on 2026-11-10.

| Flow                      | Status         | Implementation                                                                                                                                                                       |
| ------------------------- | -------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Create New Administration | ✅ Implemented | `CreateAdministration` ends the previous administration and creates the new one with the given dates; the creator is auto-assigned as President when they held that position before. |
| Manage Positions          | ✅ Implemented | `PositionController` creates and edits positions; `PositionPolicy` blocks deleting a position that is held in the current administration. Deletion is restricted to system admins.   |
| Administration History    | ✅ Implemented | `AdministrationController@index` and `@show` list every administration with its period, members and positions, highlighting the current one.                                         |

## Data Model

```mermaid
erDiagram
    positions {
        bigint id PK
        bigint community_id FK
        varchar name
        boolean is_default
        timestamp created_at
        timestamp updated_at
    }
    administrations {
        bigint id PK
        bigint community_id FK
        date started_at
        date ended_at "nullable"
        boolean is_current
        timestamp created_at
        timestamp updated_at
    }
    administration_members {
        bigint id PK
        bigint administration_id FK
        bigint user_id FK
        bigint position_id FK
        timestamp created_at
        timestamp updated_at
    }
    communities ||--o{ positions : "defines"
    communities ||--o{ administrations : "has"
    administrations ||--o{ administration_members : "composed of"
    administration_members }o--|| users : "held by"
    administration_members }o--|| positions : "occupies"
```

## Flow: Create New Administration

```mermaid
flowchart TD
    A[Admin accesses administration management] --> B[Creates new administration with start date]
    B --> C{Does a current administration exist?}
    C -->|Yes| D[Ends previous administration - sets ended_at]
    C -->|No| E[Proceeds]
    D --> E
    E --> F[Assigns members to positions]
    F --> G[Each member can hold only 1 position]
    G --> H[Administration active - is_current = true]
```

## Flow: Manage Positions

```mermaid
flowchart TD
    A[Admin accesses community positions] --> B{Action?}
    B -->|Create| C[Defines new position name]
    B -->|Edit| D[Changes existing position name]
    B -->|Remove| E{Position in use by active administration?}
    E -->|Yes| F[Blocks removal]
    E -->|No| G[Removes position]
    C --> H[Position available for assignment]
    D --> H
```

## Flow: Administration History

```mermaid
flowchart TD
    A[User accesses history] --> B[Lists all community administrations]
    B --> C[For each administration shows period and members/positions]
    C --> D[Current administration highlighted]
```
