#!/usr/bin/env bash
set -u
set -o pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LOG_DIR="$REPO_ROOT/storage/logs"
LOG_FILE="$LOG_DIR/automation.log"
SLEEP_SECONDS="${SLEEP_SECONDS:-20}"
ENABLE_GIT="${ENABLE_GIT_PULL:-1}"
ENABLE_COMPOSER="${ENABLE_COMPOSER_INSTALL:-1}"
ENABLE_NODE="${ENABLE_NODE_BUILD:-1}"
ENABLE_ARTISAN="${ENABLE_ARTISAN_COMMANDS:-1}"
RUN_VENUES_FETCH="${RUN_VENUES_FETCH:-0}"
RUN_SCHEDULE="${RUN_SCHEDULE:-0}"
RUN_QUEUE_RESTART="${RUN_QUEUE_RESTART:-0}"
RUN_TESTS="${RUN_AUTOMATION_TESTS:-0}"
USE_NPM_CI="${USE_NPM_CI:-0}"
SYNC_ENV="${SYNC_ENV_FILE:-1}"
PHP_TEST_COMMAND="${PHP_TEST_COMMAND:-php artisan test}"

if ! [[ "$SLEEP_SECONDS" =~ ^[0-9]+$ ]] || [[ "$SLEEP_SECONDS" -le 0 ]]; then
    echo "Invalid SLEEP_SECONDS value '$SLEEP_SECONDS'. Falling back to 20 seconds." >&2
    SLEEP_SECONDS=20
fi

mkdir -p "$LOG_DIR"

log() {
    local message="$1"
    printf '[%s] %s\n' "$(date '+%Y-%m-%d %H:%M:%S')" "$message" | tee -a "$LOG_FILE"
}

ensure_env_file() {
    if [[ "$SYNC_ENV" != "1" ]]; then
        return
    fi

    if [[ -f "$REPO_ROOT/.env" ]]; then
        return
    fi

    if [[ -f "$REPO_ROOT/.env.example" ]]; then
        cp "$REPO_ROOT/.env.example" "$REPO_ROOT/.env"
        log "Created .env from .env.example"
    else
        log "No .env or .env.example found; skipping environment sync."
    fi
}

run_command() {
    local description="$1"
    shift
    log "→ $description"
    if "$@"; then
        log "✔ $description"
        return 0
    else
        local exit_code=$?
        log "✖ $description (exit code: $exit_code)"
        return $exit_code
    fi
}

cleanup() {
    log "Termination signal received. Exiting loop."
    exit 0
}

trap cleanup INT TERM

log "Starting automation loop in $REPO_ROOT with a $SLEEP_SECONDS second interval."

while true; do
    log "Beginning update cycle."

    ensure_env_file

    if [[ "$ENABLE_GIT" == "1" ]]; then
        if git -C "$REPO_ROOT" rev-parse --is-inside-work-tree >/dev/null 2>&1; then
            if ! run_command "Updating repository" git -C "$REPO_ROOT" pull --rebase --stat; then
                log "Repository update failed; retrying after sleep interval."
                sleep "$SLEEP_SECONDS"
                continue
            fi
        else
            log "Directory $REPO_ROOT is not a git repository; skipping git pull."
        fi
    else
        log "Git update disabled via ENABLE_GIT_PULL=0."
    fi

    if [[ "$ENABLE_COMPOSER" == "1" ]]; then
        if command -v composer >/dev/null 2>&1 && [[ -f "$REPO_ROOT/composer.json" ]]; then
            run_command "Installing PHP dependencies" composer install --no-interaction --prefer-dist --optimize-autoloader --working-dir="$REPO_ROOT"
        else
            log "Composer not available or composer.json missing; skipping PHP dependency installation."
        fi
    else
        log "Composer install disabled via ENABLE_COMPOSER_INSTALL=0."
    fi

    if [[ "$ENABLE_NODE" == "1" ]]; then
        if command -v npm >/dev/null 2>&1 && [[ -f "$REPO_ROOT/package.json" ]]; then
            if [[ "$USE_NPM_CI" == "1" ]]; then
                run_command "Installing Node dependencies" npm ci --prefix "$REPO_ROOT"
            else
                run_command "Installing Node dependencies" npm install --prefix "$REPO_ROOT"
            fi
            run_command "Building frontend assets" npm --prefix "$REPO_ROOT" run build --if-present
        else
            log "npm not available or package.json missing; skipping Node dependency installation."
        fi
    else
        log "Node tasks disabled via ENABLE_NODE_BUILD=0."
    fi

    if [[ "$ENABLE_ARTISAN" == "1" ]]; then
        if [[ -f "$REPO_ROOT/artisan" ]]; then
            run_command "Running database migrations" php "$REPO_ROOT/artisan" migrate --force
            run_command "Caching Laravel configuration" php "$REPO_ROOT/artisan" config:cache
            run_command "Optimizing Laravel framework" php "$REPO_ROOT/artisan" optimize

            if [[ "$RUN_SCHEDULE" == "1" ]]; then
                run_command "Running scheduled tasks" php "$REPO_ROOT/artisan" schedule:run
            fi

            if [[ "$RUN_QUEUE_RESTART" == "1" ]]; then
                run_command "Restarting queue workers" php "$REPO_ROOT/artisan" queue:restart
            fi

            if [[ "$RUN_VENUES_FETCH" == "1" ]]; then
                run_command "Executing Treatwell venue fetch" php "$REPO_ROOT/artisan" venues:fetch --force
            else
                log "Treatwell venue fetch disabled via RUN_VENUES_FETCH=0."
            fi
        else
            log "Laravel artisan not found; skipping framework commands."
        fi
    else
        log "Artisan commands disabled via ENABLE_ARTISAN_COMMANDS=0."
    fi

    if [[ "$RUN_TESTS" == "1" ]]; then
        if [[ -n "$PHP_TEST_COMMAND" ]]; then
            run_command "Running automated tests" bash -lc "cd '$REPO_ROOT' && $PHP_TEST_COMMAND"
        else
            log "RUN_AUTOMATION_TESTS=1 but PHP_TEST_COMMAND is empty; skipping tests."
        fi
    fi

    log "Cycle complete. Sleeping for $SLEEP_SECONDS seconds."
    sleep "$SLEEP_SECONDS"
done
