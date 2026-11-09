# Architecture

This document describes the high-level architecture, folder structure, and key design decisions of the Community application.

## Tech Stack

| Layer    | Technology                                 |
| -------- | ------------------------------------------ |
| Backend  | PHP 8.5, Laravel 13                        |
| Frontend | React 19, Inertia.js v3                    |
| Styling  | Tailwind CSS v4                            |
| Database | SQLite (default)                           |
| Testing  | Pest v4                                    |
| Build    | Vite + Wayfinder                           |
| Auth     | Laravel Fortify (passwords, 2FA, passkeys) |

## High-Level Architecture

```mermaid
graph TD
    subgraph Client
        Browser[Browser]
    end

    subgraph Frontend["Frontend (React + Inertia)"]
        Pages[Pages]
        Components[Components]
        Layouts[Layouts]
    end

    subgraph Backend["Backend (Laravel)"]
        Routes[Routes]
        Middleware[Middleware]
        Controllers[Controllers]
        Actions[Actions]
        Models[Models]
        Policies[Policies]
    end

    subgraph Data
        DB[(SQLite)]
    end

    Browser -->|HTTP| Routes
    Routes --> Middleware
    Middleware --> Controllers
    Controllers --> Actions
    Controllers --> Models
    Controllers -->|Inertia::render| Pages
    Actions --> Models
    Models --> DB
    Policies -.->|authorize| Controllers
    Pages --> Components
    Pages --> Layouts
```

## Request Lifecycle

```mermaid
sequenceDiagram
    participant B as Browser
    participant R as Routes
    participant MW as Middleware
    participant C as Controller
    participant A as Action
    participant M as Model
    participant V as Inertia Page

    B->>R: HTTP Request
    R->>MW: EnsureCommunityMembership
    MW->>C: Controller method
    C->>A: Delegate business logic
    A->>M: Eloquent queries
    M-->>A: Data
    A-->>C: Result
    C->>V: Inertia::render('page', props)
    V-->>B: HTML / JSON (SPA navigation)
```

## Domain Model

```mermaid
erDiagram
    User ||--o{ Community : "creates / belongs to"
    Community ||--o{ Position : "defines"
    Community ||--o{ Administration : "has"
    Administration ||--o{ AdministrationMember : "has"
    AdministrationMember }o--|| User : "is"
    AdministrationMember }o--|| Position : "holds"
    Community }o--o| Administration : "current_administration"
    User }o--o| Community : "current_community"
    Community ||--o{ ContributionSetting : "configures"
    Community ||--o{ Contribution : "receives"
    Community ||--o{ ContributionReport : "generates"
    User ||--o{ Contribution : "pays"
    User ||--o{ Contribution : "reverses"

    User {
        int id
        string name
        string email
        bool is_admin
        int current_community_id
    }

    Community {
        int id
        string name
        string slug
        text description
        string address
        string city
        string state
        int created_by
        int current_administration_id
    }

    Administration {
        int id
        int community_id
        date started_at
        date ended_at
    }

    AdministrationMember {
        int id
        int administration_id
        int user_id
        int position_id
    }

    Position {
        int id
        int community_id
        string name
        bool is_default
    }

    ContributionSetting {
        int id
        int community_id
        decimal monthly_amount
        date effective_from
    }

    Contribution {
        int id
        int community_id
        int user_id "nullable"
        date reference_month
        date monthly_key "nullable"
        decimal amount
        int registered_by
        string type "monthly or donation"
        string donor_type "nullable"
        string donor_name "nullable"
        string donor_document "nullable"
        text notes "nullable"
        timestamp reversed_at "nullable"
        int reversed_by "nullable"
        text reversal_reason "nullable"
    }

    ContributionReport {
        int id
        int community_id
        date reference_month
        int total_members
        int total_paid
        int total_defaulting
        decimal total_collected
        timestamp generated_at
    }
```

## Roles & Authorization

Community membership uses the `CommunityRole` enum with three levels:

| Role        | Description                     |
| ----------- | ------------------------------- |
| `president` | Full control over the community |
| `admin`     | Administrative privileges       |
| `member`    | Standard member access          |

Authorization is enforced via **Policies** (`CommunityPolicy`, `AdministrationPolicy`, `PositionPolicy`, `MemberPolicy`, `ContributionPolicy`).

## Folder Structure

```
community/
├── app/
│   ├── Actions/                # Business logic (single-responsibility classes)
│   │   ├── Administrations/    #   CreateAdministration, AssignMemberToPosition
│   │   ├── Communities/        #   CreateCommunity
│   │   ├── Contributions/      #   RegisterContribution, RegisterDonation, ReverseContribution
│   │   └── Fortify/            #   Auth actions (registration, password, 2FA)
│   ├── Concerns/               # Traits (HasCommunities, GeneratesUniqueSlugs, ...)
│   ├── Console/                # Artisan commands
│   ├── Data/                   # Data transfer objects
│   ├── Enums/                  # CommunityRole, ContributionType, DonorType
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/            #   BrasilApiController
│   │   │   ├── Communities/    #   Community, Administration, Position, Member, Contribution controllers
│   │   │   └── Settings/       #   Profile, Security controllers
│   │   ├── Middleware/         # EnsureCommunityMembership, HandleInertiaRequests
│   │   └── Requests/           # Form request validation
│   ├── Models/                 # Eloquent models (User, Community, Administration, Contribution, ...)
│   ├── Notifications/          # Email / notification classes
│   ├── Policies/               # Authorization policies
│   ├── Providers/              # Service providers
│   └── Rules/                  # Custom validation rules (Cpf, Cnpj)
├── resources/js/
│   ├── pages/                  # Inertia page components
│   │   ├── auth/               #   Login, Register, Forgot/Reset password, Verify email
│   │   ├── communities/        #   Index, Edit, Onboarding, Administrations, Positions
│   │   ├── contributions/      #   Index, Settings, Report, Donations/Create
│   │   ├── settings/           #   Profile, Security, Appearance
│   │   ├── dashboard.tsx
│   │   └── welcome.tsx
│   ├── components/             # Reusable React components + ui/ primitives
│   ├── layouts/                # App, Auth, and Settings layouts
│   ├── hooks/                  # Custom React hooks
│   ├── lib/                    # Utility functions
│   ├── types/                  # TypeScript type definitions
│   ├── actions/                # Wayfinder generated controller actions
│   └── routes/                 # Wayfinder generated named routes
├── routes/
│   ├── web.php                 # Web routes
│   ├── settings.php            # Settings routes
│   └── console.php             # Console routes
├── database/                   # Migrations, factories, seeders
├── config/                     # Laravel configuration files
├── tests/
│   ├── Feature/                # Feature tests (Auth, Communities, Administrations, Contributions, ...)
│   └── Unit/                   # Unit tests
├── docs/                       # Documentation and wireframes
└── public/                     # Public assets
```

## Key Patterns

- **Action classes** encapsulate business logic, keeping controllers thin.
- **Inertia.js** bridges Laravel and React -- no separate API layer needed for the SPA.
- **Wayfinder** auto-generates typed TypeScript functions for routes and controller actions.
- **Multi-tenancy** is handled via `current_community_id` on the User model and the `EnsureCommunityMembership` middleware.
- **Fortify** provides authentication (login, registration, password reset, email verification, 2FA, passkeys) with no frontend opinions.
