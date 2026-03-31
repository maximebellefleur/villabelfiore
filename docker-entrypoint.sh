#!/bin/bash
set -e

ENV_FILE="/var/www/html/.env"

# Write .env from environment variables if it doesn't exist
if [ ! -f "$ENV_FILE" ]; then
    echo "Writing .env from environment variables..."
    cat > "$ENV_FILE" << EOF
APP_NAME=${APP_NAME:-Rooted}
APP_ENV=${APP_ENV:-production}
APP_DEBUG=${APP_DEBUG:-false}
APP_URL=${APP_URL:-http://localhost}

DB_HOST=${DB_HOST:-db}
DB_PORT=${DB_PORT:-3306}
DB_NAME=${DB_NAME:-rooted}
DB_USER=${DB_USER:-rooted}
DB_PASS=${DB_PASS:-rooted}

SESSION_LIFETIME=7200
SESSION_NAME=rooted_session

STORAGE_DRIVER=local
STORAGE_PATH=../storage/uploads

LOG_LEVEL=error
LOG_FILE=../storage/logs/app.log
ERROR_LOG_FILE=../storage/logs/error.log

INSTALL_LOCK=false
EOF
fi

exec "$@"
