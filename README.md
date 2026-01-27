# MES - Manufacturing Execution System

Full-stack Manufacturing Execution System with **Laravel 12** API and **Nuxt 4** frontend.

## Project Structure

```
mes/
├── backend/          # Laravel 12 API
├── frontend/         # Nuxt 4 (Vue.js)
├── docker/           # Docker configs
│   └── nginx/        # Nginx reverse proxy
├── .github/          # GitHub Actions CI/CD
├── docker-compose.yml
└── .env.example
```

## Quick Start with Docker

```bash
# 1. Clone and setup
git clone <repo-url> && cd mes
cp .env.example .env
# Edit .env with your values

# 2. Start all services
docker-compose up -d

# 3. Run migrations
docker-compose exec backend php artisan migrate --seed

# Access
# Frontend: http://localhost:3000
# API: http://localhost/api
# API Docs: http://localhost/docs
```

## Development Setup

### Backend (Laravel)

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
# API at http://localhost:8000
```

### Frontend (Nuxt)

```bash
cd frontend
npm install
npm run dev
# App at http://localhost:3000
```

## Docker Services

| Service | Port | Description |
|---------|------|-------------|
| nginx | 80 | Reverse proxy |
| frontend | 3000 | Nuxt SSR |
| backend | 9000 | PHP-FPM |
| db | 5432 | PostgreSQL |
| redis | 6379 | Cache/Queue |
| queue | - | Laravel Queue Worker |
| scheduler | - | Laravel Scheduler |

## CI/CD Pipeline

GitHub Actions workflow (`.github/workflows/ci.yml`):

1. **Test** - Run PHPUnit & frontend checks
2. **Lint** - PHP CS Fixer
3. **Build** - Docker images to GHCR
4. **Deploy** - Production deployment

## API Modules

- **Auth** - Registration, Login, Sanctum tokens
- **Engineering** - Products, BOMs, Work Centers
- **Inventory** - Locations, Stock, Transfers
- **Execution** - Manufacturing & Work Orders
- **Quality** - Points, Checks, Alerts
- **Maintenance** - Equipment, Requests, Schedules
- **Planning** - MPS, MRP Engine
- **Reporting** - Cost Analysis, OEE

## Tech Stack

| Layer | Technology |
|-------|------------|
| Backend | PHP 8.2, Laravel 12, Sanctum |
| Frontend | Nuxt 4, Vue 3, TypeScript |
| Database | PostgreSQL 15 |
| Cache | Redis 7 |
| Server | Nginx, PHP-FPM |
| CI/CD | GitHub Actions |
| Container | Docker, Docker Compose |

## Environment Variables

See `.env.example` for all required variables.

| Variable | Description |
|----------|-------------|
| `APP_KEY` | Laravel encryption key |
| `DB_PASSWORD` | Database password |
| `API_KEYS` | Comma-separated API keys |
