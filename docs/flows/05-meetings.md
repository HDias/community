# Meetings and Minutes

Only secretaries or admins can create meetings.
Minutes are free text. Attendance is recorded manually by secretary or admin.

## Data Model

```mermaid
erDiagram
    meetings {
        bigint id PK
        bigint community_id FK
        varchar title
        datetime scheduled_at
        varchar location "nullable"
        text minutes "nullable - free text"
        bigint created_by FK
        timestamp created_at
        timestamp updated_at
    }
    meeting_attendances {
        bigint id PK
        bigint meeting_id FK
        bigint user_id FK
        boolean present
        timestamp created_at
        timestamp updated_at
    }
    communities ||--o{ meetings : "holds"
    meetings ||--o{ meeting_attendances : "tracks"
    users ||--o{ meeting_attendances : "attends"
```

## Flow: Create Meeting

```mermaid
flowchart TD
    A[Secretary/Admin creates meeting] --> B[Fills in title, date/time, location]
    B --> C[Meeting created with scheduled status]
    C --> D[Community member list loaded for attendance]
```

## Flow: Record Attendance

```mermaid
flowchart TD
    A[Secretary/Admin opens meeting] --> B[Lists all community members]
    B --> C[Marks attendance individually]
    C --> D[Saves records in meeting_attendances]
    D --> E[Summary: X present out of Y members]
```

## Flow: Record Minutes

```mermaid
flowchart TD
    A[Secretary/Admin accesses meeting] --> B[Fills minutes field - free text]
    B --> C[Saves minutes linked to meeting]
    C --> D[Minutes visible to all community members]
```

## Flow: Admin Changes Future Meeting Date

```mermaid
flowchart TD
    A[Admin edits future meeting] --> B[Changes date/time or location]
    B --> C[System saves updated meeting]
    C --> D[Triggers in-system notification to all members]
    D --> E[Sends email to all members with new date]
```

## Flow: Member Views Meetings

```mermaid
flowchart TD
    A[Member accesses meetings] --> B[Lists future meetings with date and location]
    B --> C[Lists past meetings with minutes]
    C --> D[Member can read minutes of past meetings]
```

## Flow: Member Views Own Attendance History

```mermaid
flowchart TD
    A[Member accesses attendance history] --> B[Lists all past meetings]
    B --> C[Each meeting shows present or absent]
    C --> D[Summary: attended X out of Y meetings]
```
