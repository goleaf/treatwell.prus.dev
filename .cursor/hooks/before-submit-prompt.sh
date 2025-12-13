#!/usr/bin/env bash
# Cursor Hook: before-submit-prompt
# Audits prompts for security issues and sensitive data

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "${SCRIPT_DIR}/config.sh"

LOG_FILE="${HOOKS_LOG_DIR}/before-submit-prompt.log"
TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')

log() {
    echo "[${TIMESTAMP}] $1" | tee -a "${LOG_FILE}"
}

# Read prompt from stdin
PROMPT=$(cat)

# Log the prompt (truncated for privacy)
PROMPT_PREVIEW=$(echo "${PROMPT}" | head -c 200)
log "Prompt received (preview): ${PROMPT_PREVIEW}..."

# Patterns to detect sensitive data
SENSITIVE_PATTERNS=(
    "password\s*=\s*['\"]"
    "api[_-]?key\s*=\s*['\"]"
    "secret\s*=\s*['\"]"
    "token\s*=\s*['\"]"
    "private[_-]?key"
    "aws[_-]?access[_-]?key"
    "database[_-]?password"
    "DB_PASSWORD"
    "APP_KEY"
    "JWT_SECRET"
    "-----BEGIN.*PRIVATE KEY-----"
)

BLOCKED=false
DETECTED_PATTERNS=()

for pattern in "${SENSITIVE_PATTERNS[@]}"; do
    if echo "${PROMPT}" | grep -qiE "${pattern}"; then
        BLOCKED=true
        DETECTED_PATTERNS+=("${pattern}")
        log "WARNING: Detected sensitive pattern: ${pattern}"
    fi
done

if [ "${BLOCKED}" = true ]; then
    log "ERROR: Prompt contains potentially sensitive data. Blocking submission."
    log "Detected patterns: ${DETECTED_PATTERNS[*]}"
    echo "Blocked: Prompt contains potentially sensitive data" >&2
    exit 1
fi

log "Prompt approved"
exit 0

