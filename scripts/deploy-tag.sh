#!/usr/bin/env bash

set -euo pipefail

TAG="${1:-}"

if [[ -z "${TAG}" ]]; then
  echo "Usage: bash scripts/deploy-tag.sh <git-tag>"
  exit 1
fi

APP_DIR="${APP_DIR:-/var/www/daladan-api}"
PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"
QUEUE_RESTART="${QUEUE_RESTART:-true}"
PHP_FPM_RESTART_CMD="${PHP_FPM_RESTART_CMD:-}"

cd "${APP_DIR}"

if ! git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
  echo "Directory ${APP_DIR} is not a git repository."
  exit 1
fi

git fetch --tags origin
git checkout --force "${TAG}"

${COMPOSER_BIN} install --no-dev --optimize-autoloader --no-interaction --prefer-dist

${PHP_BIN} artisan migrate --force
${PHP_BIN} artisan optimize:clear
${PHP_BIN} artisan config:cache
${PHP_BIN} artisan route:cache

if [[ "${QUEUE_RESTART}" == "true" ]]; then
  ${PHP_BIN} artisan queue:restart || true
fi

if [[ -n "${PHP_FPM_RESTART_CMD}" ]]; then
  eval "${PHP_FPM_RESTART_CMD}"
fi

echo "Deployment complete for tag ${TAG}."
