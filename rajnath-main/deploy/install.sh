#!/usr/bin/env bash
# First installation on a fresh VPS (Ubuntu 24.04 with Docker). Run once, from the repository root:
#   deploy/install.sh astrorajnathjha.com owner@example.com "Acharya Dr. Rajnath Jha"
# It creates deploy/.env.production with random passwords and a new APP_KEY, builds and starts
# everything, and creates the admin account automatically (the sign-in email and a random password
# are printed once at the end of the deploy). After that, enter Razorpay,
# email/SMS and other keys in the admin (Admin › Payments setup, Email & SMS, …) — not in files.
set -euo pipefail
cd "$(dirname "$0")/.."
DOMAIN=${1:?usage: deploy/install.sh <domain> <owner-email> "<owner name>"}
EMAIL=${2:?owner email required}
NAME=${3:-Owner}
ENVF=deploy/.env.production
command -v docker >/dev/null || { echo "Install Docker first: https://docs.docker.com/engine/install/ubuntu/"; exit 1; }
rand() { head -c 48 /dev/urandom | base64 | tr -dc 'A-Za-z0-9' | head -c "${1:-40}"; }
if [ ! -f "$ENVF" ]; then
  cp deploy/.env.production.example "$ENVF"
  DBP=$(rand 40)
  sed -i \
    -e "s|^DOMAIN=.*|DOMAIN=$DOMAIN|" \
    -e "s|^APP_URL=.*|APP_URL=https://$DOMAIN|" \
    -e "s|^APP_KEY=.*|APP_KEY=base64:$(head -c 32 /dev/urandom | base64)|" \
    -e "s|^DB_PASSWORD=.*|DB_PASSWORD=$DBP|" \
    -e "s|^MARIADB_PASSWORD=.*|MARIADB_PASSWORD=$DBP|" \
    -e "s|^MARIADB_ROOT_PASSWORD=.*|MARIADB_ROOT_PASSWORD=$(rand 40)|" \
    -e "s|^ADMIN_EMAIL=.*|ADMIN_EMAIL=$EMAIL|" \
    -e "s|^ADMIN_NAME=.*|ADMIN_NAME=\"$NAME\"|" \
    "$ENVF"
  chmod 600 "$ENVF"
  echo "Created $ENVF. KEEP A COPY of APP_KEY somewhere safe: it encrypts saved keys and passwords."
else
  echo "$ENVF already exists — keeping it."
fi
ln -sfn .env.production deploy/.env
FIRST_RUN=1 deploy/deploy.sh
echo
echo "Done. Sign in at https://$DOMAIN/staff/login with the admin email and password printed above."
echo "Lost the password? docker compose -f deploy/docker-compose.yml exec app php artisan arj:ensure-admin --reset --email=$EMAIL"
echo "Then in the admin: My account (own password + two-factor) → Email & SMS → Payments setup → AI assistant → Languages."
echo "Add the backup cron:  15 2 * * * $(pwd)/deploy/backup.sh >> /var/log/arj-backup.log 2>&1"
