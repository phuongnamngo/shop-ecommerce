.PHONY: up down build logs ps migrate seed test artisan composer composer-install shell-api shell-web

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

seed:
	docker compose exec api php artisan db:seed --force

test:
	docker compose exec api php artisan test

artisan:
	docker compose exec api php artisan $(CMD)

# make composer-install
composer-install:
	docker compose exec api composer install

# make composer CMD="require laravel/sanctum"
# make composer CMD="require spatie/laravel-permission --no-interaction"
# make composer CMD="update"
composer:
	docker compose exec api composer $(CMD)

shell-api:
	docker compose exec api sh

shell-web:
	docker compose exec web sh
