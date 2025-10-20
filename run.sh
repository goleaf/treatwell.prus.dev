#!/usr/bin/env bash
set -u
set -o pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LOG_DIR="$REPO_ROOT/storage/logs"
LOG_FILE="$LOG_DIR/automation.log"
SLEEP_SECONDS="${SLEEP_SECONDS:-20}"

if ! [[ "$SLEEP_SECONDS" =~ ^[0-9]+$ ]] || [[ "$SLEEP_SECONDS" -le 0 ]]; then
    echo "Invalid SLEEP_SECONDS value '$SLEEP_SECONDS'. Falling back to 20 seconds." >&2
    SLEEP_SECONDS=20
fi

mkdir -p "$LOG_DIR"

log() {
    local message="$1"
    printf '[%s] %s\n' "$(date '+%Y-%m-%d %H:%M:%S')" "$message" | tee -a "$LOG_FILE"
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

    if git -C "$REPO_ROOT" rev-parse --is-inside-work-tree >/dev/null 2>&1; then
        if ! run_command "Updating repository" git -C "$REPO_ROOT" pull --rebase --stat; then
            log "Repository update failed; retrying after sleep interval."
            sleep "$SLEEP_SECONDS"
            continue
        fi
    else
        log "Directory $REPO_ROOT is not a git repository; skipping git pull."
    fi

    if command -v composer >/dev/null 2>&1 && [[ -f "$REPO_ROOT/composer.json" ]]; then
        run_command "Installing PHP dependencies" composer install --no-interaction --prefer-dist --working-dir="$REPO_ROOT"
    else
        log "Composer not available or composer.json missing; skipping PHP dependency installation."
    fi

    if command -v npm >/dev/null 2>&1 && [[ -f "$REPO_ROOT/package.json" ]]; then
        run_command "Installing Node dependencies" npm install --prefix "$REPO_ROOT"
        run_command "Building frontend assets" npm --prefix "$REPO_ROOT" run build --if-present
    else
        log "npm not available or package.json missing; skipping Node dependency installation."
    fi

    if [[ -f "$REPO_ROOT/artisan" ]]; then
        run_command "Running database migrations" php "$REPO_ROOT/artisan" migrate --force
        run_command "Caching Laravel configuration" php "$REPO_ROOT/artisan" config:cache
        run_command "Optimizing Laravel framework" php "$REPO_ROOT/artisan" optimize
        run_command "Executing Treatwell venue fetch" php "$REPO_ROOT/artisan" venues:fetch --fetch-api --fetch-sitemap
    else
        log "Laravel artisan not found; skipping framework commands."
    fi

    log "Cycle complete. Sleeping for $SLEEP_SECONDS seconds."
    sleep "$SLEEP_SECONDS"
done
