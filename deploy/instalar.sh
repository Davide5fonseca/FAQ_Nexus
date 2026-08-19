#!/usr/bin/env bash
# =============================================================================
# Instalação automática num servidor Ubuntu 22.04 / 24.04 (ou Debian 12).
#
# O que faz, por ordem:
#   1. Instala PHP 8.3, PostgreSQL, Nginx, Composer, Certbot.
#   2. Cria a base de dados e o utilizador PostgreSQL.
#   3. Instala a aplicação em /var/www/procedimentos.
#   4. Configura o Nginx, obtém o certificado HTTPS e cria a conta de administrador.
#
# Como usar (com a pasta da aplicação já copiada para o servidor, p. ex. em /root/FAQ):
#   sudo bash /root/FAQ/deploy/instalar.sh procedimentos.exemplo.pt
#
# Pode correr-se mais do que uma vez sem estragar nada.
# =============================================================================

set -euo pipefail

DOMINIO="${1:-}"
if [[ -z "$DOMINIO" ]]; then
  echo "Utilização: sudo bash instalar.sh o-vosso-dominio.pt"
  exit 1
fi
if [[ "$(id -u)" -ne 0 ]]; then
  echo "Execute com sudo."
  exit 1
fi

ORIGEM="$(cd "$(dirname "$0")/.." && pwd)"   # pasta da aplicação (onde está o artisan)
APP_DIR="/var/www/procedimentos"
DB_NAME="procedimentos"
DB_USER="procedimentos"

echo "==> 1/6  A instalar pacotes (PHP, PostgreSQL, Nginx, Composer, Certbot)…"
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get install -y -qq software-properties-common ca-certificates curl unzip git rsync >/dev/null
# Ubuntu 22.04 não traz PHP 8.3 de origem: acrescenta o repositório oficial do PHP se for preciso.
if ! apt-cache show php8.3-fpm >/dev/null 2>&1; then
  add-apt-repository -y ppa:ondrej/php >/dev/null 2>&1 || true
  apt-get update -qq
fi
apt-get install -y -qq php8.3-fpm php8.3-cli php8.3-pgsql php8.3-mbstring php8.3-xml php8.3-intl php8.3-curl php8.3-zip php8.3-bcmath \
  postgresql postgresql-contrib nginx certbot python3-certbot-nginx >/dev/null
if ! command -v composer >/dev/null; then
  curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer >/dev/null
fi
systemctl enable --now postgresql php8.3-fpm nginx >/dev/null

echo "==> 2/6  A criar base de dados PostgreSQL…"
if [[ -f "$APP_DIR/.env" ]] && grep -q "^DB_PASSWORD=" "$APP_DIR/.env"; then
  DB_PASS="$(grep '^DB_PASSWORD=' "$APP_DIR/.env" | cut -d= -f2- | tr -d '"')"
else
  DB_PASS="$(tr -dc 'A-Za-z0-9' </dev/urandom | head -c 32)"
fi
sudo -u postgres psql -tAc "SELECT 1 FROM pg_roles WHERE rolname='$DB_USER'" | grep -q 1 \
  || sudo -u postgres psql -c "CREATE ROLE $DB_USER LOGIN PASSWORD '$DB_PASS';"
sudo -u postgres psql -c "ALTER ROLE $DB_USER WITH PASSWORD '$DB_PASS';" >/dev/null
sudo -u postgres psql -tAc "SELECT 1 FROM pg_database WHERE datname='$DB_NAME'" | grep -q 1 \
  || sudo -u postgres psql -c "CREATE DATABASE $DB_NAME OWNER $DB_USER ENCODING 'UTF8' TEMPLATE template0;"

echo "==> 3/6  A copiar a aplicação para $APP_DIR…"
mkdir -p "$APP_DIR"
rsync -a --delete --exclude '.env' --exclude 'vendor' --exclude 'storage/logs/*' --exclude 'storage/framework/sessions/*' \
  --exclude 'storage/framework/cache/*' --exclude 'storage/framework/views/*' --exclude '.git' "$ORIGEM/" "$APP_DIR/"
cd "$APP_DIR"
if [[ ! -f .env ]]; then
  cp .env.example .env
  sed -i "s|^APP_URL=.*|APP_URL=https://$DOMINIO|" .env
  sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=$DB_PASS|" .env
  sed -i "s|^SESSION_SECURE_COOKIE=.*|SESSION_SECURE_COOKIE=true|" .env
  grep -q "^SESSION_SECURE_COOKIE=" .env || echo "SESSION_SECURE_COOKIE=true" >> .env
fi
sudo -u www-data composer install --no-dev --optimize-autoloader --no-interaction --quiet 2>/dev/null \
  || COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction --quiet
grep -q "^APP_KEY=base64" .env || php artisan key:generate --force --quiet
php artisan migrate --force
php artisan config:cache --quiet
php artisan route:cache --quiet
php artisan view:cache --quiet
chown -R www-data:www-data "$APP_DIR"
chmod -R ug+rwX "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"
chmod 640 "$APP_DIR/.env"

echo "==> 4/6  A configurar o Nginx…"
sed "s/procedimentos.exemplo.pt/$DOMINIO/g" "$APP_DIR/deploy/nginx.conf" > /etc/nginx/sites-available/procedimentos
ln -sf /etc/nginx/sites-available/procedimentos /etc/nginx/sites-enabled/procedimentos
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl reload nginx

echo "==> 5/6  A obter certificado HTTPS para $DOMINIO…"
if certbot --nginx -d "$DOMINIO" --non-interactive --agree-tos --register-unsafely-without-email --redirect; then
  echo "    HTTPS activo."
else
  echo "    AVISO: não foi possível obter o certificado agora (o domínio já aponta para este servidor?)."
  echo "    Quando apontar, corra:  sudo certbot --nginx -d $DOMINIO"
fi

echo "==> 6/6  Cópias de segurança diárias…"
install -m 755 "$APP_DIR/deploy/backup.sh" /usr/local/bin/backup-procedimentos
( crontab -l 2>/dev/null | grep -v backup-procedimentos; echo "30 2 * * * /usr/local/bin/backup-procedimentos >> /var/log/backup-procedimentos.log 2>&1" ) | crontab -
/usr/local/bin/backup-procedimentos || true

echo
echo "=============================================================="
echo " Instalação concluída.  Endereço: https://$DOMINIO"
echo
if ! sudo -u www-data php "$APP_DIR/artisan" tinker --execute='echo \App\Models\User::count();' 2>/dev/null | grep -q '^[1-9]'; then
  echo " Falta criar a conta de administrador. Corra agora:"
  echo "   cd $APP_DIR && sudo -u www-data php artisan app:criar-admin"
fi
echo " Palavra-passe da base de dados guardada em $APP_DIR/.env"
echo "=============================================================="
