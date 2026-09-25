#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

if [[ -f .env ]]; then
  set -a
  # shellcheck disable=SC1091
  source .env
  set +a
fi

: "${POSTGRES_USER:?POSTGRES_USER is required}"
: "${POSTGRES_DB:?POSTGRES_DB is required}"

dump="${1:-}"
if [[ -z "$dump" || "$dump" != backups/* || "$dump" == *..* ]]; then
  echo "Pass a dump path inside backups/" >&2
  exit 1
fi

if [[ ! -f "$dump" ]]; then
  echo "Dump not found: $dump" >&2
  exit 1
fi

drill="watch_restore_drill"

cleanup() {
  docker compose exec -T postgres psql -U "$POSTGRES_USER" -d postgres -c "DROP DATABASE IF EXISTS ${drill}" >/dev/null
}
trap cleanup EXIT

if ! docker compose exec -T postgres psql -U "$POSTGRES_USER" -d postgres -c "CREATE DATABASE ${drill}" >/dev/null; then
  echo "Refusing to restore because ${drill} could not be created" >&2
  trap - EXIT
  exit 1
fi

docker compose exec -T postgres pg_restore -U "$POSTGRES_USER" -d "$drill" --no-owner < "$dump"

app_count="$(docker compose exec -T postgres psql -U "$POSTGRES_USER" -d "$POSTGRES_DB" -tAc 'SELECT count(*) FROM admin_users')"
drill_count="$(docker compose exec -T postgres psql -U "$POSTGRES_USER" -d "$drill" -tAc 'SELECT count(*) FROM admin_users')"
app_count="$(echo "$app_count" | tr -d '[:space:]')"
drill_count="$(echo "$drill_count" | tr -d '[:space:]')"

if [[ "$app_count" != "$drill_count" ]]; then
  echo "admin_users count mismatch app=${app_count} drill=${drill_count}" >&2
  exit 1
fi

echo "restore drill ok admin_users=${drill_count}"
