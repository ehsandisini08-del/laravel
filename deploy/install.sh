#!/usr/bin/env bash
#
# Billnet — One-click production installer (Ubuntu 20.04/22.04)
#
# Jalankan sebagai root:
#   sudo bash deploy/install.sh
#
# Mengerjakan A-Z:
#   sistem + firewall -> PHP 8.4 + Composer -> Node 22 -> Nginx
#   -> clone repo & deploy Laravel -> queue worker + scheduler cron
#   -> Nginx vhost + Certbot SSL (domain diminta di akhir)
#

# Bersihkan CRLF bila file di-transfer dari Windows (mencegah `\r` merusak bash).
sed -i 's/\r$//' "$0" 2>/dev/null || true

# Pastikan dijalankan dengan bash (dibutuhkan `set -o pipefail` & `[[ ]]`).
# Jika dipanggil via `sh`/interpreter lain, eksekusi ulang dengan bash.
if [ -z "${BASH_VERSION:-}" ]; then
    echo "[install] Skrip ini membutuhkan bash — menjalankan ulang dengan bash..."
    exec bash "$0" "$@"
fi

set -euo pipefail

# ---------------------------------------------------------------------------
# Konfigurasi (sesuaikan bila perlu)
# ---------------------------------------------------------------------------
REPO_URL="https://github.com/ehsandisini08-del/laravel.git"
BRANCH="main"
APP_PATH="/var/www/billnet"
PHP_VERSION="8.4"
TZ="Asia/Jakarta"
CERTBOT_EMAIL=""
SKIP_SSL="${SKIP_SSL:-0}"   # set SKIP_SSL=1 untuk tanpa certbot

# ---------------------------------------------------------------------------
# Utilitas
# ---------------------------------------------------------------------------
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; NC='\033[0m'
log()  { echo -e "${GREEN}[install]${NC} $*"; }
warn() { echo -e "${YELLOW}[warn]${NC} $*"; }
err()  { echo -e "${RED}[error]${NC} $*"; exit 1; }

require_root() {
  [[ "$(id -u)" -eq 0 ]] || err "Harus dijalankan sebagai root (sudo)."
}

run_quiet() {
  # Runs a command, streaming output only on failure.
  if ! "$@" >/tmp/billnet-cmd.log 2>&1; then
    cat /tmp/billnet-cmd.log
    err "Perintah gagal: $*"
  fi
}

# ---------------------------------------------------------------------------
# 1. Prep sistem
# ---------------------------------------------------------------------------
prep_system() {
  log "Persiapan sistem & repository base..."
  export DEBIAN_FRONTEND=noninteractive
  export TZ=UTC
  apt-get update -y
  apt-get upgrade -y
  apt-get install -y software-properties-common curl wget git unzip zip ca-certificates gnupg lsb-release ufw cron
  timedatectl set-timezone "${TZ}" || true
}

# ---------------------------------------------------------------------------
# 2. Firewall
# ---------------------------------------------------------------------------
setup_firewall() {
  log "Konfigurasi firewall (ufw)..."
  ufw allow 22/tcp >/dev/null 2>&1 || true
  ufw allow 80/tcp >/dev/null 2>&1 || true
  ufw allow 443/tcp >/dev/null 2>&1 || true
  ufw --force enable >/dev/null 2>&1 || true
}

# ---------------------------------------------------------------------------
# 3. PHP
# ---------------------------------------------------------------------------
install_php() {
  log "Install PHP ${PHP_VERSION} + ekstensi..."
  if ! dpkg -s "php${PHP_VERSION}-fpm" >/dev/null 2>&1; then
    add-apt-repository -y ppa:ondrej/php >/dev/null 2>&1
    apt-get update -y
  fi
  apt-get install -y \
    "php${PHP_VERSION}-fpm" \
    "php${PHP_VERSION}-cli" \
    "php${PHP_VERSION}-common" \
    "php${PHP_VERSION}-curl" \
    "php${PHP_VERSION}-mbstring" \
    "php${PHP_VERSION}-xml" \
    "php${PHP_VERSION}-zip" \
    "php${PHP_VERSION}-bcmath" \
    "php${PHP_VERSION}-gd" \
    "php${PHP_VERSION}-intl" \
    "php${PHP_VERSION}-sqlite3" \
    "php${PHP_VERSION}-mysql" \
    "php${PHP_VERSION}-opcache" \
    "php${PHP_VERSION}-fileinfo" \
    "php${PHP_VERSION}-sockets"

  # PHP-FPM tuning
  local fpm_ini="/etc/php/${PHP_VERSION}/fpm/php.ini"
  local cli_ini="/etc/php/${PHP_VERSION}/cli/php.ini"
  for ini in "$fpm_ini" "$cli_ini"; do
    sed -i 's/^upload_max_filesize.*/upload_max_filesize = 20M/' "$ini"
    sed -i 's/^post_max_size.*/post_max_size = 25M/' "$ini"
    sed -i 's/^max_execution_time.*/max_execution_time = 300/' "$ini"
    sed -i 's/^memory_limit.*/memory_limit = 256M/' "$ini"
    sed -i 's/^max_input_time.*/max_input_time = 300/' "$ini"
  done

  systemctl enable "php${PHP_VERSION}-fpm" >/dev/null
  systemctl start "php${PHP_VERSION}-fpm"
  log "PHP ${PHP_VERSION} siap."
}

# ---------------------------------------------------------------------------
# 4. Composer
# ---------------------------------------------------------------------------
install_composer() {
  log "Install Composer..."
  if ! command -v composer >/dev/null 2>&1; then
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
  fi
  composer --version
}

# ---------------------------------------------------------------------------
# 5. Node.js
# ---------------------------------------------------------------------------
install_node() {
  log "Install Node.js 22 LTS + npm..."
  if ! command -v node >/dev/null 2>&1; then
    curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
    apt-get install -y nodejs
  fi
  node --version
  npm --version
}

# ---------------------------------------------------------------------------
# 6. Nginx
# ---------------------------------------------------------------------------
install_nginx() {
  log "Install Nginx..."
  if ! command -v nginx >/dev/null 2>&1; then
    apt-get install -y nginx
  fi
  rm -f /etc/nginx/sites-enabled/default
  rm -f /etc/nginx/sites-available/default
}

# ---------------------------------------------------------------------------
# 7. Deploy aplikasi Laravel
# ---------------------------------------------------------------------------
deploy_app() {
  log "Clone & deploy aplikasi dari ${REPO_URL}..."
  mkdir -p "$(dirname "$APP_PATH")"
  # Daftarkan repo sebagai safe.directory (root & www-data) agar git tidak menolak.
  git config --system --add safe.directory "$APP_PATH" 2>/dev/null || true
  if [[ ! -d "$APP_PATH/.git" ]]; then
    git clone -b "$BRANCH" "$REPO_URL" "$APP_PATH"
  else
    warn "Direktori aplikasi sudah ada — menarik kode terbaru dari repo..."
    git -C "$APP_PATH" fetch origin "$BRANCH"
    git -C "$APP_PATH" reset --hard "origin/$BRANCH"
  fi

  cd "$APP_PATH"

  log "Composer install (--no-dev)..."
  export COMPOSER_ALLOW_SUPERUSER=1 COMPOSER_NO_INTERACTION=1
  run_quiet composer install --no-dev --no-interaction --optimize-autoloader

  log "Siapkan .env..."
  if [[ ! -f .env ]]; then
    cp .env.example .env 2>/dev/null || true
  fi
  sed -i 's/^APP_ENV=.*/APP_ENV=production/' .env
  sed -i 's/^APP_DEBUG=.*/APP_DEBUG=false/' .env
  sed -i 's|^APP_URL=.*|APP_URL=https://your-domain.com|' .env
  # Pastikan database sqlite + session/cache/queue
  sed -i 's/^DB_CONNECTION=.*/DB_CONNECTION=sqlite/' .env

  touch database/database.sqlite
  log "Generate APP_KEY..."
  php artisan key:generate --force

  log "Migrasi & seed database..."
  php artisan migrate --force
  php artisan db:seed --force

  log "Symlink storage..."
  php artisan storage:link

  log "Build frontend (npm + Vite)..."
  if [[ -f package-lock.json ]]; then
    npm ci --no-audit --no-fund
  else
    npm install --no-audit --no-fund
  fi
  npm run build

  log "Cache Laravel ini..."
  php artisan optimize 2>/dev/null || true

  log "Atur permission (www-data)..."
  chown -R www-data:www-data "$APP_PATH"
  find "$APP_PATH/storage" -type d -exec chmod 775 {} \;
  find "$APP_PATH/bootstrap/cache" -type d -exec chmod 775 {} \;
}

# ---------------------------------------------------------------------------
# 8. Queue worker (systemd)
# ---------------------------------------------------------------------------
setup_queue() {
  log "Buat service queue worker (systemd)..."
  cat > /etc/systemd/system/billnet-queue.service <<EOF
[Unit]
Description=Billnet Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
WorkingDirectory=$APP_PATH
ExecStart=/usr/bin/php${PHP_VERSION} $APP_PATH/artisan queue:work --sleep=3 --tries=3 --max-time=3600
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
EOF
  systemctl daemon-reload
  systemctl enable billnet-queue >/dev/null 2>&1
  systemctl restart billnet-queue
  log "Queue worker aktif."
}

# ---------------------------------------------------------------------------
# 9. Scheduler cron
# ---------------------------------------------------------------------------
setup_cron() {
  log "Pasang cron scheduler..."
  command -v crontab >/dev/null 2>&1 || apt-get install -y cron >/dev/null 2>&1 || true

  local cron_line="* * * * * cd $APP_PATH && /usr/bin/php${PHP_VERSION} artisan schedule:run >> /dev/null 2>&1"
  local existing filtered
  existing="$(crontab -l 2>/dev/null || true)"
  filtered="$(printf '%s\n' "$existing" | grep -v 'artisan schedule:run' || true)"
  { printf '%s\n' "$filtered"; printf '%s\n' "$cron_line"; } | crontab - 2>/dev/null || true
  log "Cron scheduler terpasang."
}

# ---------------------------------------------------------------------------
# 10. Prompt DOMAIN + SSL
# ---------------------------------------------------------------------------
prompt_domain() {
  if [[ -z "${DOMAIN:-}" ]]; then
    echo ""
    if [[ -t 0 ]]; then
      read -r -p "Masukkan domain (misal: billnet.example.com): " DOMAIN
    elif [[ -e /dev/tty ]]; then
      read -r -p "Masukkan domain (misal: billnet.example.com): " DOMAIN < /dev/tty
    else
      err "Tidak ada terminal untuk input. Set variabel DOMAIN lalu jalankan ulang (mis. DOMAIN=billnet.example.com sudo bash install.sh)."
    fi
  fi
  [[ -n "${DOMAIN}" ]] || err "Domain wajib diisi (atau set SKIP_SSL=1)."

  if [[ -z "${CERTBOT_EMAIL}" ]]; then
    if [[ -t 0 ]]; then
      read -r -p "Email untuk Let's Encrypt (untuk notifikasi SSL): " CERTBOT_EMAIL
    elif [[ -e /dev/tty ]]; then
      read -r -p "Email untuk Let's Encrypt (untuk notifikasi SSL): " CERTBOT_EMAIL < /dev/tty
    fi
  fi
}

# ---------------------------------------------------------------------------
# 11) Nginx vhost
# ---------------------------------------------------------------------------
setup_nginx_site() {
  log "Buat konfigurasi Nginx untuk ${DOMAIN}..."
  cat > /etc/nginx/sites-available/billnet <<EOF
server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN};

    root ${APP_PATH}/public;
    index index.php index.html;

    client_max_body_size 25M;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        fastcgi_pass unix:/run/php/php${PHP_VERSION}-fpm.sock;
        fastcgi_index index.php;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF
  ln -sf /etc/nginx/sites-available/billnet /etc/nginx/sites-enabled/billnet
  nginx -t
  systemctl reload nginx
}

update_app_url() {
  local url="https://${DOMAIN}"
  if [[ "${SKIP_SSL}" != "0" ]]; then
    url="http://${DOMAIN}"
  fi
  sed -i "s|^APP_URL=.*|APP_URL=${url}|" "$APP_PATH/.env"
  ( cd "$APP_PATH" && /usr/bin/php${PHP_VERSION} artisan config:clear )
  log "APP_URL => ${url}"
}

# ---------------------------------------------------------------------------
# 12. SSL dengan Let's Encrypt
# ---------------------------------------------------------------------------
setup_ssl() {
  if [[ "${SKIP_SSL}" != "0" ]]; then
    warn "SKIP_SSL=1 — melewati instalasi SSL (HTTP only)."
    return
  fi
  log "Install certbot & aman-kan SSL via Let's Encrypt..."
  apt-get install -y certbot python3-certbot-nginx
  certbot --nginx -d "${DOMAIN}" \
    --non-interactive --agree-tos \
    --email "${CERTBOT_EMAIL:-admin@${DOMAIN}}" \
    --redirect \
    --keep-until-expiring
  systemctl enable certbot.timer >/dev/null 2>&1 || true
}

# ---------------------------------------------------------------------------
# 12. Optimize & perbaiki permission akhir (penting: cegah 500)
# ---------------------------------------------------------------------------
fix_permissions() {
  log "Optimize cache & perbaiki permission akhir (www-data)..."
  ( cd "$APP_PATH" && /usr/bin/php${PHP_VERSION} artisan optimize ) >/dev/null 2>&1 || true
  chown -R www-data:www-data "$APP_PATH"
  find "$APP_PATH/storage" "$APP_PATH/bootstrap/cache" -type d -exec chmod 775 {} \;
  find "$APP_PATH/storage/logs" -type f -exec chmod 664 {} \; 2>/dev/null || true
  log "Permission akhir selesai."
}

# ---------------------------------------------------------------------------
# 13) Randomisasi password user seed & ringkasan
# ---------------------------------------------------------------------------
finalize() {
  log "Randomisasi password user seeded..."
  local p1 p2 p3
  p1="$(openssl rand -base64 12 | tr -dc 'A-Za-z0-9' | cut -c1-12)"
  p2="$(openssl rand -base64 12 | tr -dc 'A-Za-z0-9' | cut -c1-12)"
  p3="$(openssl rand -base64 12 | tr -dc 'A-Za-z0-9' | cut -c1-12)"
  cd "$APP_PATH"
  # Gunakan DB::table + Hash::make (sekali) agar password benar-benar bcrypt,
  # tidak bergantung pada cast Eloquent (mencegah error "does not use Bcrypt").
  /usr/bin/php${PHP_VERSION} artisan tinker --execute="
    DB::table('users')->where('email','admin@example.com')->update(['password'=> Hash::make('${p1}')]);
    DB::table('users')->where('email','superadmin@example.com')->update(['password'=> Hash::make('${p2}')]);
    DB::table('users')->where('email','demo@example.com')->update(['password'=> Hash::make('${p3}')]);
  " >/dev/null 2>&1 || true

  if [[ "${SKIP_SSL}" != "0" ]]; then
    app_url="http://${DOMAIN}"
  else
    app_url="https://${DOMAIN}"
  fi

  echo ""
  echo "===================================================================="
  echo -e "${GREEN}  INSTALLASI SELESAI${NC}"
  echo "===================================================================="
  echo "  URL      : ${app_url}"
  echo "  Lokasi   : ${APP_PATH}"
  echo "  PHP-FPM  : php${PHP_VERSION}-fpm"
  echo "  Queue    : billnet-queue (systemd)"
  echo "  Scheduler: cron tiap menit"
  echo ""
  echo "  Akun login awal (default):"
  echo "    admin@example.com      / ${p1}   (developer)"
  echo "    superadmin@example.com / ${p2}   (superadmin)"
  echo "    demo@example.com       / ${p3}   (admin)"
  echo ""
  echo "  Catatan:"
  echo "    - Segera ganti password-password di atas."
  echo "    - Atur FIREBASE_CREDENTIALS di .env untuk push notification."
  echo "    - Update aplikasi dari dashboard admin (menu Update)."
  echo "===================================================================="
}

# ---------------------------------------------------------------------------
# Setup WhatsApp Gateway (Baileys) — berjalan di port 3001
# ---------------------------------------------------------------------------
setup_whatsapp_gateway() {
  log "Setup WhatsApp Gateway (Baileys, port 3001)..."
  local GW_DIR="$APP_PATH/wa-gateway"
  [[ -d "$GW_DIR" ]] || { warn "Folder wa-gateway tidak ditemukan — lewati setup gateway."; return; }

  if [[ "${SKIP_SSL}" != "0" ]]; then web_url="http://${DOMAIN}"; else web_url="https://${DOMAIN}"; fi

  # Token & secret sama untuk gateway & Laravel
  local gw_token gw_secret
  gw_token="$(grep -oP '^BAILEYS_GATEWAY_TOKEN=\K.*' "$APP_PATH/.env" 2>/dev/null || true)"
  [[ -n "$gw_token" ]] || gw_token="$(openssl rand -hex 16)"
  gw_secret="$(grep -oP '^BAILEYS_WEBHOOK_SECRET=\K.*' "$APP_PATH/.env" 2>/dev/null || true)"
  [[ -n "$gw_secret" ]] || gw_secret="whsec_baileys_2026"

  cat > "$GW_DIR/.env" <<EOF
PORT=3001
API_TOKEN=${gw_token}
WEBHOOK_URL=${web_url}/webhooks/whatsapp
WEBHOOK_SECRET=${gw_secret}
SESSION_DIR=./sessions
LOG_LEVEL=info
EOF

  sed -i "s|^BAILEYS_GATEWAY_TOKEN=.*|BAILEYS_GATEWAY_TOKEN=${gw_token}|" "$APP_PATH/.env"
  sed -i "s|^BAILEYS_WEBHOOK_SECRET=.*|BAILEYS_WEBHOOK_SECRET=${gw_secret}|" "$APP_PATH/.env"

  # Session bekas (dev) dibersihkan → scan QR baru saat produksi
  rm -rf "$GW_DIR"/sessions/* 2>/dev/null || true
  mkdir -p "$GW_DIR/sessions"

  ( cd "$GW_DIR" && npm ci --no-audit --no-fund ) >/dev/null 2>&1 \
    || ( cd "$GW_DIR" && npm install --no-audit --no-fund )

  cat > /etc/systemd/system/wa-gateway.service <<EOF
[Unit]
Description=Billnet WA Gateway (Baileys)
After=network.target

[Service]
User=www-data
Group=www-data
WorkingDirectory=$GW_DIR
ExecStart=/usr/bin/node src/index.js
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
EOF
  chown -R www-data:www-data "$GW_DIR/sessions" 2>/dev/null || true
  systemctl daemon-reload
  systemctl enable wa-gateway >/dev/null 2>&1
  systemctl restart wa-gateway || echo "Gagal start wa-gateway — cek: journalctl -u wa-gateway"
  log "WhatsApp Gateway aktif di port 3001."
}

# ---------------------------------------------------------------------------
# MAIN
# ---------------------------------------------------------------------------
main() {
  require_root
  prep_system
  setup_firewall
  install_php
  install_composer
  install_node
  install_nginx
  deploy_app
  setup_queue
  setup_cron

  # =====================  TAHAP TERAKHIR: Nginx + SSL =====================
  # Di sini skrip berhenti menunggu input domain, lalu otomatis:
  #   a) membuat konfigurasi Nginx untuk domain
  #   b) mengatur APP_URL di .env
  #   c) membuat sertifikat SSL via Let's Encrypt (certbot)
  prompt_domain
  setup_nginx_site
  update_app_url
  setup_whatsapp_gateway
  setup_ssl
  fix_permissions
  finalize
}

main "$@"
