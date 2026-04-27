# Soccer Manager API

A RESTful API for managing soccer teams, players, and transfers. Built with Laravel following SOLID principles using a Repository-Service pattern. Authenticated with Sanctum and localized in English and Georgian.

## Architecture

The project follows a layered architecture with clear separation of concerns:

- **Controllers** — thin, handle HTTP layer only, delegate to services
- **Services** — business logic (team creation, transfer purchases, player listing)
- **Repositories** — data access abstraction, all DB queries go through repository interfaces
- **Form Requests** — input validation
- **API Resources** — response formatting
- **Model Scopes** — reusable query filters (e.g. market filtering by position, price, country)
## Tech Stack

- **Laravel** with API versioning (`/api/v1/...`)
- **Sanctum** for token-based authentication
- **Pest** for feature testing
- **Laravel Pint** for code style (PSR-12)
- **Laravel Sail** for Docker-based development
- **PostgreSQL** with proper migrations and indexing

## Setup

### With Docker (Laravel Sail)

```bash
cp .env.example .env
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
```

The API will be available at `http://localhost`.

### Manual Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

## Authentication

All API requests (except register/login) require a Bearer token:

```
Authorization: Bearer {token}
```

For localization, add the `X-App-Locale` header with `en` or `ka`.

## API Endpoints

### Auth

| Method | Endpoint        | Description |
|--------|-----------------|-------------|
| POST   | /api/register   | Register    |
| POST   | /api/login      | Login       |
| POST   | /api/logout     | Logout      |

### Team

| Method | Endpoint      | Description  |
|--------|---------------|--------------|
| GET    | /api/v1/team  | Show team    |
| PATCH  | /api/v1/team  | Update team  |

### Players

| Method | Endpoint                  | Description    |
|--------|---------------------------|----------------|
| GET    | /api/v1/players/{id}      | Show player    |
| PATCH  | /api/v1/players/{id}      | Update player  |

### Transfer Market

| Method | Endpoint                                    | Description      |
|--------|---------------------------------------------|------------------|
| GET    | /api/v1/transfer-listings                   | Browse market    |
| POST   | /api/v1/transfer-listings                   | List a player    |
| DELETE | /api/v1/transfer-listings/{id}              | Cancel listing   |
| POST   | /api/v1/transfer-listings/{id}/purchase     | Purchase player  |

**Market filters:** `?position_id=&country_id=&team_id=&min_price=&max_price=`

## Domain Rules

- Each user gets one team on registration
- Teams start with $5,000,000 budget and 20 players (3 GK, 6 DF, 6 MF, 5 AT)
- Each player starts at $1,000,000 market value
- Player ages range from 18 to 40
- Owners can edit team name, team country, and player first name, last name, country
- Players can be listed on the transfer market with an asking price
- Purchases deduct from buyer budget, add to seller budget, and transfer ownership
- After purchase, player market value increases by a random 10-100%

## Testing

```bash
php artisan test
# or with Sail
./vendor/bin/sail artisan test
```

## Code Style

```bash
vendor/bin/pint
```
