#!/usr/bin/env bash
# =============================================================================
# setup.sh — Installation complète du monorepo : Composer, npm, logos, clés JWT, assets du site
# -----------------------------------------------------------------------------
# bin/setup.sh
#
# Ne crée, ne lit et ne modifie aucun fichier .env.local ni .env.develop : les secrets
# (APP_SECRET, DATABASE_URL, MAILER_DSN, JWT_PASSPHRASE…) s'y définissent à la main.
# =============================================================================
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

step() { printf '\n\033[1;34m==> %s\033[0m\n' "$1"; }

require() {
  command -v "$1" >/dev/null 2>&1 || { echo "❌ $1 est requis." >&2; exit 1; }
}

require php
require composer
require npm

step "Dépendances PHP (credit-core, credit_api, credit_front, outils d'analyse)"
composer install --no-interaction --working-dir=packages/credit-core
composer install --no-interaction --working-dir=apps/credit_api
composer install --no-interaction --working-dir=apps/credit_front
if [ -f composer.json ]; then
  composer install --no-interaction
fi

step "Dépendances npm (workspaces : credit-front-core, credit_tunnel, credit_client, credit_crm, credit_front)"
npm install

step "Logos des partenaires → apps/credit_api/public/assets/logos"
mkdir -p apps/credit_api/public/assets/logos
cp packages/credit-core/assets/logos/*.svg apps/credit_api/public/assets/logos/

step "Clés JWT de credit_api (passphrase lue par Symfony dans JWT_PASSPHRASE)"
# Sous Windows (Git Bash), l'extension OpenSSL de PHP a besoin d'un openssl.cnf
if [ -z "${OPENSSL_CONF:-}" ] && command -v openssl >/dev/null 2>&1; then
  ssl_dir="$(openssl version -d 2>/dev/null | sed -E 's/^OPENSSLDIR: "(.*)"$/\1/')"
  if [ -n "$ssl_dir" ] && [ -f "$ssl_dir/openssl.cnf" ]; then
    if command -v cygpath >/dev/null 2>&1; then
      export OPENSSL_CONF="$(cygpath -m "$ssl_dir/openssl.cnf")"
    else
      export OPENSSL_CONF="$ssl_dir/openssl.cnf"
    fi
  fi
fi
(cd apps/credit_api && php bin/console lexik:jwt:generate-keypair --skip-if-exists)

step "Assets du site vitrine (SCSS et TypeScript → apps/credit_front/public)"
npm run compile --workspace credit_front

step "Tests (domaine credit-core, règles de priorisation de credit_api)"
(cd packages/credit-core && vendor/bin/phpunit --colors=never) || echo "⚠️ Tests du domaine en échec : voir ci-dessus."
(cd apps/credit_api && vendor/bin/phpunit --colors=never) || echo "⚠️ Tests de l'API en échec : voir ci-dessus."

cat <<'EOF'

✅ Installation terminée.

Étapes manuelles (secrets, jamais versionnés) :
  1. Renseigner dans apps/credit_api/.env.local : APP_SECRET, DATABASE_URL (MySQL 8 / MariaDB),
     MAILER_DSN et JWT_PASSPHRASE (régénérer les clés si la passphrase change :
     php bin/console lexik:jwt:generate-keypair --overwrite).
  2. Renseigner APP_SECRET dans apps/credit_front/.env.local.
  3. Créer la base et appliquer les migrations :
       cd apps/credit_api && php bin/console doctrine:database:create --if-not-exists && php bin/console doctrine:migrations:migrate -n
  4. Données de démonstration (facultatif, efface la base) : php bin/console doctrine:fixtures:load -n
     → 3 conseillers (alex.durand@example.com…) et 210 dossiers couvrant les priorités P1 à P3.
     Ou créer un conseiller : php bin/console app:advisor:create conseiller@exemple.fr Prénom Nom
  5. Planifier les relances e-mail J+1 / J+7 (cron, toutes les heures) :
       0 * * * * php <chemin>/apps/credit_api/bin/console app:dossiers:send-reminders
  6. Lancer les applications : bin/dev.sh
EOF
