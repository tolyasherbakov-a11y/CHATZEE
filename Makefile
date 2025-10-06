SHELL := /bin/bash

up:
	docker compose up -d --build

down:
	docker compose down

logs:
	docker compose logs -f --tail=200

reverb:
	docker compose exec reverb sh -lc "php artisan reverb:start --host=0.0.0.0 --port=8080"

queue:
	docker compose exec queue sh -lc "php artisan queue:work --sleep=1 --tries=3 --backoff=5"

scheduler:
	docker compose exec scheduler sh -lc "php artisan schedule:work"

test:
	docker compose exec app sh -lc "php artisan test -q"

composer-install:
	docker compose exec app sh -lc "composer install"

key-generate:
	docker compose exec app sh -lc "php artisan key:generate"

permissions:
	docker compose exec app sh -lc "chown -R www-data:www-data storage bootstrap/cache && chmod -R ug+rwX storage bootstrap/cache"
