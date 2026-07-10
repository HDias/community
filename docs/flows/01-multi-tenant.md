# Multi-tenant: Community

Based on the prayer-community structure (`communities` + `community_user`).
Each community is independent. A user can belong to more than one.

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
