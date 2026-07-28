.PHONY: up down build logs ps migrate artisan shell-api shell-web

up:
	docker compose up -d

down:
	docker compose down

build:
	docker compose build

logs:
	docker compose logs -f --tail=200

ps:
	docker compose ps

migrate:
	docker compose exec api php artisan migrate --force

artisan:
	docker compose exec api php artisan $(CMD)

shell-api:
	docker compose exec api sh

shell-web:
	docker compose exec web sh
