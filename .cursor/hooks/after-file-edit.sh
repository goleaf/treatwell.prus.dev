#!/usr/bin/env bash
# Cursor Hook: after-file-edit
# Runs code formatting and optional tests after file edits

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "${SCRIPT_DIR}/config.sh"

LOG_FILE="${HOOKS_LOG_DIR}/after-file-edit.log"
TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')

log() {
    echo "[${TIMESTAMP}] $1" | tee -a "${LOG_FILE}"
}

# Read edited file path from stdin
EDITED_FILE=$(cat)

if [ -z "${EDITED_FILE}" ]; then
    log "No file path provided"
    exit 0
fi

# Resolve absolute path
if [[ "${EDITED_FILE}" != /* ]]; then
    EDITED_FILE="${PROJECT_ROOT}/${EDITED_FILE}"
fi

log "File edited: ${EDITED_FILE}"

# Only process PHP files
if [[ ! "${EDITED_FILE}" =~ \.php$ ]]; then
    log "Skipping non-PHP file: ${EDITED_FILE}"
    exit 0
fi

# Auto-format with Pint
if [ "${ENABLE_AUTO_FORMAT}" = "true" ] && [ -f "${PINT_PATH}" ]; then
    log "Running Laravel Pint on: ${EDITED_FILE}"
    if "${PINT_PATH}" "${EDITED_FILE}" >> "${LOG_FILE}" 2>&1; then
        log "✓ Pint formatting completed"
    else
        log "✗ Pint formatting failed (exit code: $?)"
    fi
elif [ "${ENABLE_AUTO_FORMAT}" = "true" ]; then
    log "WARNING: Pint not found at ${PINT_PATH}"
fi

# Run PHPStan (optional)
if [ "${ENABLE_PHPSTAN}" = "true" ] && [ -f "${PHPSTAN_PATH}" ]; then
    log "Running PHPStan analysis"
    if cd "${PROJECT_ROOT}" && "${PHPSTAN_PATH}" analyse "${EDITED_FILE}" --no-progress >> "${LOG_FILE}" 2>&1; then
        log "✓ PHPStan analysis completed"
    else
        log "✗ PHPStan analysis found issues (exit code: $?)"
    fi
fi

# Run tests (optional)
if [ "${ENABLE_AUTO_TEST}" = "true" ]; then
    log "Running tests for edited file"
    
    # Try to find corresponding test file
    TEST_FILE=""
    if [[ "${EDITED_FILE}" =~ ^${PROJECT_ROOT}/app/(.+)\.php$ ]]; then
        RELATIVE_PATH="${BASH_REMATCH[1]}"
        TEST_FILE="${PROJECT_ROOT}/tests/Unit/${RELATIVE_PATH}Test.php"
        
        # Also check Feature tests
        if [ ! -f "${TEST_FILE}" ]; then
            TEST_FILE="${PROJECT_ROOT}/tests/Feature/${RELATIVE_PATH}Test.php"
        fi
    fi
    
    if [ -n "${TEST_FILE}" ] && [ -f "${TEST_FILE}" ]; then
        log "Running test file: ${TEST_FILE}"
        if cd "${PROJECT_ROOT}" && php artisan test "${TEST_FILE}" >> "${LOG_FILE}" 2>&1; then
            log "✓ Tests passed"
        else
            log "✗ Tests failed (exit code: $?)"
        fi
    else
        log "No corresponding test file found for: ${EDITED_FILE}"
    fi
fi

log "File edit processing completed"
exit 0

