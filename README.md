# project-ecommerce

Monorepo: Laravel API (`apps/api`) + Next.js (`apps/web`).

## Prerequisites

- Docker + Docker Compose
- Make (optional nhưng khuyến nghị)

## Quick start

```bash
cp .env.example .env
make build
make up
```

- Web: http://localhost:3000
- API: http://localhost:8000
- Postgres: `localhost:5432` (trong container hostname = `postgres`)
- Redis: `localhost:6379` (hostname = `redis`)

## Useful commands

```bash
make logs
make migrate
make artisan CMD="route:list"
make shell-api
make shell-web
make down
```

## Notes

- Root `.env` là nguồn cho Compose; không commit file `.env`.
- Laravel trong Docker dùng `DB_HOST=postgres`, `REDIS_HOST=redis`.
