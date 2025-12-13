#!/usr/bin/env bash
# Cursor Hook: stop
# Cleanup script when agent stops

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "${SCRIPT_DIR}/config.sh"

LOG_FILE="${HOOKS_LOG_DIR}/stop.log"
TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')

log() {
    echo "[${TIMESTAMP}] $1" | tee -a "${LOG_FILE}"
}

log "Agent stop hook triggered"

# Clean up temporary files in project root
TEMP_PATTERNS=(
    "*.tmp"
    "*.temp"
    "*.swp"
    "*.swo"
    "*~"
    ".DS_Store"
)

CLEANED_COUNT=0
for pattern in "${TEMP_PATTERNS[@]}"; do
    find "${PROJECT_ROOT}" -maxdepth 3 -name "${pattern}" -type f -delete 2>/dev/null | while read -r file; do
        if [ -n "${file}" ]; then
            log "Cleaned up: ${file}"
            ((CLEANED_COUNT++)) || true
        fi
    done
done

# Archive old logs (older than 7 days)
if [ -d "${HOOKS_LOG_DIR}" ]; then
    find "${HOOKS_LOG_DIR}" -name "*.log" -type f -mtime +7 -exec gzip {} \; 2>/dev/null | while read -r file; do
        if [ -n "${file}" ]; then
            log "Archived log: ${file}"
        fi
    done
fi

# Clean up Laravel cache if needed (optional, commented out by default)
# log "Clearing Laravel cache"
# cd "${PROJECT_ROOT}" && php artisan cache:clear >/dev/null 2>&1 || true

log "Cleanup completed"
exit 0

