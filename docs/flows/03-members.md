# Member Registration

Member = User with login. Required personal data + custom fields per community.
Each community can create additional fields (e.g. "Quilombola Registry", "CRQ") with name and type defined by admin.

## Data Model

```mermaid
erDiagram
    users {
        bigint id PK
        varchar name
        varchar social_name "nullable"
        varchar nickname "nullable"
        varchar email UK
        varchar cpf UK
        date birth_date
        varchar profession "nullable"
        varchar phone "nullable"
        varchar address_street "nullable"
        varchar address_number "nullable"
        varchar address_neighborhood "nullable"
        varchar address_city "nullable"
        varchar address_state "nullable"
        varchar address_zip "nullable"
        varchar password
        bigint current_community_id FK "nullable"
        timestamp created_at
        timestamp updated_at
    }
    custom_fields {
        bigint id PK
        bigint community_id FK
        varchar name
        varchar type "text or number or date"
        boolean is_required
        timestamp created_at
        timestamp updated_at
    }
    custom_field_values {
        bigint id PK
        bigint custom_field_id FK
        bigint user_id FK
        text value
        timestamp created_at
        timestamp updated_at
    }
    communities ||--o{ custom_fields : "defines"
    custom_fields ||--o{ custom_field_values : "has values"
    users ||--o{ custom_field_values : "fills"
```

## Flow: Register Member

```mermaid
flowchart TD
    A[Admin registers new member] --> B[Fills required data]
    B --> C[Name, CPF, Email, Birth Date, Phone]
    C --> D[Fills optional data]
    D --> E[Social name, nickname, profession, address]
    E --> F{Does community have custom fields?}
    F -->|Yes| G[Fills custom fields]
    F -->|No| H[Creates user with temporary password]
    G --> H
    H --> I[Associates to community via community_user]
    I --> J[Member receives email with credentials]
```

## Flow: Admin Manages Custom Fields

```mermaid
flowchart TD
    A[Admin accesses community settings] --> B[Manage registration fields]
    B --> C{Action?}
    C -->|Create| D[Defines name, type and required flag]
    C -->|Edit| E[Changes existing field]
    C -->|Remove| F[Removes field and its values]
    D --> G[Field available in member registration]
    E --> G
```
