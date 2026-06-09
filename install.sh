#!/usr/bin/env bash
#
# Retaint Analytics for Magento 2 — one-shot installer.
#
# Copies the module into <magento>/app/code/Retaint/Analytics, enables it,
# runs the correct build steps for the store's deploy mode, then prints the
# store's secret token, the feed endpoints, and a self-check.
#
# Usage:
#   ./install.sh [/path/to/magento]
#
#   - If a path is given, that is used as the Magento root.
#   - Otherwise the script tries the MAGENTO_ROOT env var, then auto-detects
#     it (handles the case where this module already sits in app/code).
#
# Copyright (c) 2026 Retaint (https://retaint.com)
# License: OSL-3.0  (https://opensource.org/licenses/OSL-3.0)

set -euo pipefail

MODULE_NAME="Retaint_Analytics"
MODULE_PATH="app/code/Retaint/Analytics"

# --- pretty output -----------------------------------------------------------
if [ -t 1 ]; then
    BOLD="$(printf '\033[1m')"; GREEN="$(printf '\033[32m')"
    YELLOW="$(printf '\033[33m')"; RED="$(printf '\033[31m')"; RESET="$(printf '\033[0m')"
else
    BOLD=""; GREEN=""; YELLOW=""; RED=""; RESET=""
fi
info() { echo "${BOLD}==>${RESET} $*"; }
ok()   { echo "${GREEN}✓${RESET} $*"; }
warn() { echo "${YELLOW}!${RESET} $*"; }
die()  { echo "${RED}✗ $*${RESET}" >&2; exit 1; }

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# --- locate the Magento root -------------------------------------------------
find_magento_root() {
    # 1) explicit argument
    if [ "${1:-}" != "" ]; then echo "$1"; return; fi
    # 2) environment variable
    if [ "${MAGENTO_ROOT:-}" != "" ]; then echo "$MAGENTO_ROOT"; return; fi
    # 3) module already placed inside app/code/Retaint/Analytics -> climb 4 levels
    if [ -f "$SCRIPT_DIR/../../../../bin/magento" ]; then
        ( cd "$SCRIPT_DIR/../../../.." && pwd ); return
    fi
    # 4) walk up from the current directory looking for bin/magento
    local dir; dir="$(pwd)"
    while [ "$dir" != "/" ]; do
        if [ -f "$dir/bin/magento" ]; then echo "$dir"; return; fi
        dir="$(dirname "$dir")"
    done
    echo ""
}

MAGE_ROOT="$(find_magento_root "${1:-}")"
[ -n "$MAGE_ROOT" ] || die "Could not find a Magento root. Pass it explicitly: ./install.sh /path/to/magento"
[ -f "$MAGE_ROOT/bin/magento" ] || die "No bin/magento at: $MAGE_ROOT"
command -v php >/dev/null 2>&1 || die "php is not on PATH."

MAGE_ROOT="$(cd "$MAGE_ROOT" && pwd)"
info "Magento root: $MAGE_ROOT"

# --- copy module into app/code (unless we are already there) -----------------
DEST="$MAGE_ROOT/$MODULE_PATH"
if [ "$SCRIPT_DIR" != "$DEST" ]; then
    info "Copying module into $MODULE_PATH"
    mkdir -p "$DEST"
    # Copy everything except VCS metadata and this script's own dev docs.
    if command -v rsync >/dev/null 2>&1; then
        rsync -a --delete --exclude '.git' "$SCRIPT_DIR/" "$DEST/"
    else
        cp -R "$SCRIPT_DIR/." "$DEST/"
    fi
    ok "Module files in place"
else
    info "Module already located in app/code — installing in place"
fi

cd "$MAGE_ROOT"

# --- detect deploy mode ------------------------------------------------------
MODE="$(php bin/magento deploy:mode:show 2>/dev/null | grep -oE '(developer|production|default)' | head -1 || true)"
MODE="${MODE:-default}"
info "Deploy mode: $MODE"

run() { echo "   ${BOLD}\$ php bin/magento $*${RESET}"; php bin/magento "$@"; }

# --- build -------------------------------------------------------------------
if [ "$MODE" = "production" ]; then
    info "Production mode — enabling maintenance window for the build"
    run maintenance:enable
    trap 'php bin/magento maintenance:disable >/dev/null 2>&1 || true' EXIT
fi

run module:enable "$MODULE_NAME"
run setup:upgrade

if [ "$MODE" = "production" ]; then
    run setup:di:compile
fi

run cache:flush

if [ "$MODE" = "production" ]; then
    run maintenance:disable
    trap - EXIT
fi

ok "Build complete"

# --- read back results -------------------------------------------------------
STATUS="$(php bin/magento module:status "$MODULE_NAME" 2>/dev/null || true)"
echo "$STATUS" | grep -qi "enabled" || warn "Module does not report as enabled — check the output above."

TOKEN="$(php bin/magento config:show retaint_settings/general/security_token 2>/dev/null | tail -1 | tr -d '[:space:]' || true)"
BASE="$(php bin/magento config:show web/secure/base_url 2>/dev/null | tail -1 | tr -d '[:space:]' || true)"
[ -n "$BASE" ] || BASE="$(php bin/magento config:show web/unsecure/base_url 2>/dev/null | tail -1 | tr -d '[:space:]' || true)"
BASE="${BASE%/}"

# --- self-check (version endpoint is unauthenticated) ------------------------
SELFCHECK="skipped (curl not found)"
if command -v curl >/dev/null 2>&1 && [ -n "$BASE" ]; then
    CODE="$(curl -ks -o /dev/null -w '%{http_code}' "$BASE/retaint/module/version" || echo 000)"
    if [ "$CODE" = "200" ]; then SELFCHECK="${GREEN}OK (HTTP 200)${RESET}"; else SELFCHECK="${RED}HTTP $CODE${RESET}"; fi
fi

# --- summary -----------------------------------------------------------------
cat <<EOF

${BOLD}────────────────────────────────────────────────────────${RESET}
${BOLD} Retaint Analytics installed${RESET}
${BOLD}────────────────────────────────────────────────────────${RESET}
 Store URL ......... ${BASE:-<unknown>}
 Secret Key ........ ${TOKEN:-<run: php bin/magento config:show retaint_settings/general/security_token>}
 Version endpoint .. ${SELFCHECK}

 Next steps:
   1. Admin: Stores → Configuration → Retaint → Analytics
   2. Click "Connect to Retaint" (or paste the Store URL + Secret Key
      into your Retaint dashboard).

 The collector must send this header on every request:
   X-Retaint-Token: ${TOKEN:-<your secret key>}

 Verify an authenticated feed:
   curl -H "X-Retaint-Token: ${TOKEN:-<token>}" "${BASE}/retaint/module/orders?page_size=5"
${BOLD}────────────────────────────────────────────────────────${RESET}
EOF
