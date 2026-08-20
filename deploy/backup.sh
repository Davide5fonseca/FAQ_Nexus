#!/usr/bin/env bash
# =============================================================================
# Cópia de segurança da base de dados (PostgreSQL) da Knowledgebase Nexus.
#
# O que faz:
#   1. Exporta toda a base de dados para um ficheiro comprimido com a data.
#   2. Guarda-o em /var/backups/procedimentos/.
#   3. Apaga cópias com mais de 30 dias (ajustável em DIAS_A_GUARDAR).
#
# Instalação (uma vez):
#   sudo cp /var/www/procedimentos/deploy/backup.sh /usr/local/bin/backup-procedimentos
#   sudo chmod +x /usr/local/bin/backup-procedimentos
#   sudo /usr/local/bin/backup-procedimentos        # testar
#
# Automatizar todos os dias às 02:30:
#   sudo crontab -e
#   e acrescentar a linha:
#   30 2 * * * /usr/local/bin/backup-procedimentos >> /var/log/backup-procedimentos.log 2>&1
#
# Restaurar uma cópia (ver também o README):
#   gunzip -c /var/backups/procedimentos/procedimentos-2026-08-19_0230.sql.gz | sudo -u postgres psql procedimentos
# =============================================================================

set -euo pipefail

APP_DIR="/var/www/procedimentos"
DESTINO="/var/backups/procedimentos"
DIAS_A_GUARDAR=30

# Lê o nome da base de dados, utilizador e palavra-passe do ficheiro .env da aplicação
ler_env() { grep -E "^$1=" "$APP_DIR/.env" | head -1 | cut -d'=' -f2- | tr -d '"' | tr -d "'"; }
DB_HOST="$(ler_env DB_HOST)";         DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="$(ler_env DB_PORT)";         DB_PORT="${DB_PORT:-5432}"
DB_DATABASE="$(ler_env DB_DATABASE)"
DB_USERNAME="$(ler_env DB_USERNAME)"
DB_PASSWORD="$(ler_env DB_PASSWORD)"

if [[ -z "$DB_DATABASE" || -z "$DB_USERNAME" ]]; then
  echo "ERRO: não consegui ler DB_DATABASE/DB_USERNAME de $APP_DIR/.env" >&2
  exit 1
fi

mkdir -p "$DESTINO"
chmod 700 "$DESTINO"

DATA="$(date +%Y-%m-%d_%H%M)"
FICHEIRO="$DESTINO/procedimentos-$DATA.sql.gz"

export PGPASSWORD="$DB_PASSWORD"
pg_dump --host="$DB_HOST" --port="$DB_PORT" --username="$DB_USERNAME" --no-owner --no-privileges "$DB_DATABASE" \
  | gzip -9 > "$FICHEIRO"
unset PGPASSWORD

chmod 600 "$FICHEIRO"
echo "$(date '+%Y-%m-%d %H:%M:%S') OK  cópia criada: $FICHEIRO ($(du -h "$FICHEIRO" | cut -f1))"

# Limpeza de cópias antigas
find "$DESTINO" -name 'procedimentos-*.sql.gz' -type f -mtime +"$DIAS_A_GUARDAR" -print -delete | sed 's/^/   apagada cópia antiga: /'
