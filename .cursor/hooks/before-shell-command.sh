#!/usr/bin/env bash
# Cursor Hook: before-shell-command
# Validates and blocks dangerous shell commands

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "${SCRIPT_DIR}/config.sh"

LOG_FILE="${HOOKS_LOG_DIR}/before-shell-command.log"
TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')

log() {
    echo "[${TIMESTAMP}] $1" | tee -a "${LOG_FILE}"
}

# Read command from stdin
COMMAND=$(cat)

log "Command received: ${COMMAND}"

if [ "${BLOCK_DANGEROUS_COMMANDS}" != "true" ]; then
    log "Command validation disabled, allowing command"
    exit 0
fi

# Dangerous command patterns to block
DANGEROUS_PATTERNS=(
    "rm\s+-rf\s+/"
    "rm\s+-rf\s+~"
    "rm\s+-rf\s+\*"
    "format\s+[a-z]:"
    "del\s+/s\s+/q"
    "mkfs\."
    "dd\s+if="
    ":\s*\(\)\s*\{\s*:\s*\|\s*:\s*&\s*\}\s*;"
    "chmod\s+777"
    "chmod\s+-R\s+777"
    "mysql.*drop\s+database"
    "psql.*drop\s+database"
    ">.*/dev/sd[a-z]"
    "shutdown\s+-h\s+now"
    "reboot"
    "halt"
)

# Check for dangerous patterns
for pattern in "${DANGEROUS_PATTERNS[@]}"; do
    if echo "${COMMAND}" | grep -qiE "${pattern}"; then
        log "ERROR: Blocked dangerous command pattern: ${pattern}"
        log "Full command: ${COMMAND}"
        echo "Blocked: Dangerous command detected" >&2
        exit 1
    fi
done

# Safe Laravel/development commands (always allow)
SAFE_PATTERNS=(
    "^php\s+artisan"
    "^composer\s+"
    "^npm\s+"
    "^vendor/bin/pint"
    "^vendor/bin/phpstan"
    "^vendor/bin/phpunit"
    "^php\s+artisan\s+test"
    "^git\s+"
    "^cd\s+"
    "^ls\s*"
    "^cat\s+"
    "^grep\s+"
    "^find\s+"
    "^pwd"
    "^echo\s+"
    "^mkdir\s+"
    "^touch\s+"
)

# Check if command matches safe patterns
IS_SAFE=false
for pattern in "${SAFE_PATTERNS[@]}"; do
    if echo "${COMMAND}" | grep -qiE "${pattern}"; then
        IS_SAFE=true
        break
    fi
done

if [ "${IS_SAFE}" = true ]; then
    log "Command approved (safe pattern match)"
    exit 0
fi

# For other commands, allow but log
log "Command allowed (not in dangerous list): ${COMMAND}"
exit 0

