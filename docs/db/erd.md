# Database Schema (ERD)

DB-neutral schema (see [ADR-0001](../adr/0001-postgres-but-db-neutral.md)). Tenant-owned tables
carry `tenant_id`. This reflects the **current** schema and grows with each feature slice.

```mermaid
erDiagram
    tenants ||--o{ domains : has
    tenants ||--o{ tenant_user : "has members"
    users   ||--o{ tenant_user : "joins clubs"
    tenants ||--o{ courts : owns
    tenants ||--o{ tournaments : runs
    tenants ||--o{ teams : has
    courts  ||--o{ bookings : "booked as"
    users   ||--o{ bookings : "booked by"
    tournaments ||--o{ teams : fields
    teams   ||--o{ team_player : roster
    users   ||--o{ team_player : "plays in"

    tenants {
        string id PK "uuid (or slug)"
        string name
        string slug UK
        json   data "stancl virtual columns"
    }
    users {
        bigint id PK
        string name
        string email UK
        boolean is_platform_admin
    }
    tenant_user {
        bigint id PK
        string tenant_id FK
        bigint user_id FK
    }
    courts {
        bigint id PK
        string tenant_id FK
        string name
        string surface "hard|clay|grass|carpet"
        boolean is_active
    }
    bookings {
        bigint id PK
        string tenant_id FK
        bigint court_id FK
        bigint user_id FK
        datetime starts_at
        datetime ends_at
        string status "reserved|cancelled|completed"
    }
    tournaments {
        bigint id PK
        string tenant_id FK
        string name
        date starts_on
        date ends_on
        string status "draft|open|in_progress|completed"
    }
    teams {
        bigint id PK
        string tenant_id FK
        bigint tournament_id FK "nullable"
        string name
    }
    team_player {
        bigint id PK
        string tenant_id FK
        bigint team_id FK
        bigint user_id FK
    }
```

> Roles/permissions tables (`roles`, `permissions`, `model_has_roles`, …) come from
> `spatie/laravel-permission` with `team_foreign_key = tenant_id` (string). See
> [ADR-0005](../adr/0005-single-database-multitenancy.md).

## Evolution note

The feature slices will introduce: `court_availability`, `court_blackouts`, `pricing_rules`,
`memberships`/`invitations`, tournament `categories`, `registrations`, `draws`, `matches`,
`scores`, billing `plans`/`subscriptions`/`invoices`, and `notifications`. As primary keys
migrate to UUID per ADR-0001, this diagram is updated accordingly.
