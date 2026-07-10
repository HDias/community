# Bylaws

Versioned with full history. Only admin can edit.
On edit, all members receive email + in-system notification with changes and full content.
Members must confirm reading ("I have read and acknowledge").

## Data Model

```mermaid
erDiagram
    bylaws {
        bigint id PK
        bigint community_id FK
        int version
        longtext content
        text changes_summary "nullable"
        bigint published_by FK
        timestamp published_at
        timestamp created_at
        timestamp updated_at
    }
    bylaw_acknowledgements {
        bigint id PK
        bigint bylaw_id FK
        bigint user_id FK
        timestamp acknowledged_at "nullable"
        timestamp created_at
        timestamp updated_at
    }
    notifications {
        uuid id PK
        bigint user_id FK
        varchar type
        json data
        timestamp read_at "nullable"
        timestamp created_at
        timestamp updated_at
    }
    communities ||--o{ bylaws : "has versions"
    bylaws ||--o{ bylaw_acknowledgements : "requires ack"
    users ||--o{ bylaw_acknowledgements : "confirms reading"
    users ||--o{ notifications : "receives"
```

## Flow: Edit Bylaws

```mermaid
flowchart TD
    A[Admin accesses bylaws] --> B[Edits content]
    B --> C[Fills in changes summary]
    C --> D[System creates new version - version + 1]
    D --> E[Triggers notification for all members]
    E --> F[Sends email with changes + full content]
    F --> G[Creates pending acknowledgement record per member]
```

## Flow: Reading Confirmation

```mermaid
flowchart TD
    A[Member receives notification/email] --> B[Accesses bylaws in system]
    B --> C[Views current version + changes summary]
    C --> D[Clicks 'I have read and acknowledge']
    D --> E[System records acknowledged_at]
    E --> F[Notification marked as read]
```

## Flow: Bylaws History (Admin Only)

```mermaid
flowchart TD
    A[Admin accesses bylaws] --> B[Views current version]
    B --> C[Lists all versions with date and publisher]
    C --> D[Can compare versions - diff]
```

## Flow: Admin Panel - Reading Status

```mermaid
flowchart TD
    A[Admin accesses bylaws panel] --> B[Views current version]
    B --> C[Lists members who confirmed reading]
    C --> D[Lists pending members]
    D --> E{Resend reminder?}
    E -->|Yes| F[Resends email to pending members]
    E -->|No| G[Done]
```

## Flow: Member Views Bylaws

```mermaid
flowchart TD
    A[Member accesses bylaws] --> B[Views current version only]
    B --> C{Pending acknowledgement?}
    C -->|Yes| D[Shows changes summary + full content]
    D --> E[Clicks 'I have read and acknowledge']
    E --> F[Acknowledgement recorded]
    C -->|No| G[Reads current bylaws content]
```
