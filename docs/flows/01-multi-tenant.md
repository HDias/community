# Multi-tenant: Community

Based on the prayer-community structure (`communities` + `community_user`).
Each community is independent. A user can belong to more than one.

## Implementation Status

> Checked on 2026-11-10.

| Flow             | Status                   | Implementation                                                                                                                                                                                                                             |
| ---------------- | ------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Create Community | ✅ Implemented           | `CreateCommunity` creates the community, attaches the creator as President, seeds the default positions and the first administration, and switches the user to the new community.                                                          |
| Join Community   | ⚠️ Partially implemented | `RegisterMember` creates the user, profile and `community_user` pivot from the members area, and users can belong to multiple communities and switch contexts. There is no flow to attach an already-registered user to another community. |

## Data Model

```mermaid
erDiagram
    communities {
        bigint id PK
        varchar name
        varchar slug UK
        text description
        varchar address
        varchar city
        varchar state
        bigint created_by FK
        timestamp created_at
        timestamp updated_at
    }
    community_user {
        bigint id PK
        bigint community_id FK
        bigint user_id FK
        timestamp joined_at
        timestamp created_at
        timestamp updated_at
    }
    communities ||--o{ community_user : "has members"
    users ||--o{ community_user : "belongs to"
```

## Flow: Create Community

```mermaid
flowchart TD
    A[Admin creates community] --> B[Fills in name, address, city, state]
    B --> C[System generates unique slug]
    C --> D[Creator is associated as member]
    D --> E[Creator receives 'president' role in 1st administration]
    E --> F[Community active]
```

## Flow: Join Community

```mermaid
flowchart TD
    A[User registers/logs in] --> B[Admin adds member to community]
    B --> C[Record created in community_user]
    C --> D[Member has access to community context]
```
