#!/usr/bin/env bash
set -euo pipefail

if [ ! -f "composer.json" ]; then
  echo "[*] Creating Laravel 12 skeleton..."
  docker compose run --rm app composer create-project laravel/laravel . "^12.0"
fi

echo "[*] Installing core packages"
docker compose run --rm app composer require laravel/sanctum spatie/laravel-permission

echo "[*] Install Reverb (broadcasting stack)"
docker compose run --rm app php artisan install:broadcasting

echo "[*] App key"
docker compose run --rm app php artisan key:generate

echo "[*] Migrate"
docker compose run --rm app php artisan migrate
