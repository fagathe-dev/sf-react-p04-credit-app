#!/usr/bin/env bash
# =============================================================================
# dev.sh — Lance les 5 applications en développement (Ctrl+C arrête tout)
# -----------------------------------------------------------------------------
#   credit_api     http://localhost:8000   (serveur PHP intégré)
#   credit_front   http://localhost:8001   (serveur PHP intégré + compilation SCSS/TS en watch)
#   credit_crm     http://localhost:5170   (Vite)
#   credit_client  http://localhost:5171   (Vite)
#   credit_tunnel  http://localhost:5172   (Vite)
#
# Variables facultatives : API_PORT, FRONT_PORT, HOST (défaut 127.0.0.1).
# =============================================================================
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

HOST="${HOST:-127.0.0.1}"
API_PORT="${API_PORT:-8000}"
FRONT_PORT="${FRONT_PORT:-8001}"

if [ ! -d node_modules ] || [ ! -d apps/credit_api/vendor ] || [ ! -d apps/credit_front/vendor ]; then
  echo "Dépendances absentes : lancez d'abord bin/setup.sh" >&2
  exit 1
fi

pids=()

# Lance une commande en arrière-plan, sortie préfixée par le nom de l'application
run() {
  local name="$1" color="$2"
  shift 2
  (
    "$@" 2>&1 | while IFS= read -r line; do
      printf '\033[%sm[%s]\033[0m %s\n' "$color" "$name" "$line"
    done
  ) &
  pids+=("$!")
}

stop() {
  trap - INT TERM EXIT
  echo
  echo "Arrêt des applications…"
  for pid in "${pids[@]}"; do
    pkill -P "$pid" 2>/dev/null || true
    kill "$pid" 2>/dev/null || true
  done
  wait 2>/dev/null || true
}
trap stop INT TERM EXIT

run api     "1;32" php -S "$HOST:$API_PORT" -t apps/credit_api/public
run front   "1;36" php -S "$HOST:$FRONT_PORT" -t apps/credit_front/public
run assets  "0;36" npm run dev --workspace credit_front
run crm     "1;35" npm run dev --workspace credit_crm
run client  "1;33" npm run dev --workspace credit_client
run tunnel  "1;34" npm run dev --workspace credit_tunnel

cat <<EOF
credit_api     http://localhost:$API_PORT
credit_front   http://localhost:$FRONT_PORT
credit_crm     http://localhost:5170
credit_client  http://localhost:5171
credit_tunnel  http://localhost:5172
EOF

wait
