# 🚀 Instrukcja Instalacji GitHub Webhook dla tryawait.dev

## 📋 Wymagania

- Dostęp SSH do serwera smarthost
- PHP 7.4+ lub Bash
- Git zainstalowany na serwerze
- Sklonowane repozytorium na serwerze

## 🔧 Instalacja (krok po kroku)

### 1. Przygotuj serwer

Zaloguj się przez SSH do serwera:

```bash
ssh uzytkownik@tryawait.dev
```

### 2. Sklonuj repozytorium (jeśli jeszcze nie jest)

```bash
# Utwórz katalog dla repozytorium
mkdir -p /home/tryawait.dev/repository
cd /home/tryawait.dev/repository

# Sklonuj repozytorium
git clone https://github.com/DARK-YT/tryawait_dev.git .

# Upewnij się, że jesteś na branchu main
git checkout main
```

### 3. Utwórz katalog dla logów

```bash
mkdir -p /home/tryawait.dev/logs
chmod 755 /home/tryawait.dev/logs
```

### 4. Zainstaluj skrypt webhook

#### Opcja A: Skrypt PHP (zalecane)

```bash
# Skopiuj webhook.php do katalogu public_html
cp webhook.php /home/tryawait.dev/public_html/webhook.php

# Ustaw odpowiednie uprawnienia
chmod 755 /home/tryawait.dev/public_html/webhook.php
```

**WAŻNE:** Edytuj `webhook.php` i ustaw:
- `WEBHOOK_SECRET` - wygeneruj silny losowy ciąg (np. używając `openssl rand -hex 32`)
- `REPO_PATH` - ścieżka do sklonowanego repozytorium
- `LOG_FILE` - ścieżka do pliku z logami

```bash
nano /home/tryawait.dev/public_html/webhook.php
```

Zmień te linie:
```php
define('REPO_PATH', '/home/tryawait.dev/repository');
define('WEBHOOK_SECRET', 'WYGENERUJ_LOSOWY_KLUCZ_TUTAJ');
define('LOG_FILE', '/home/tryawait.dev/logs/webhook.log');
```

#### Opcja B: Skrypt Bash (alternatywa)

Utwórz plik `webhook.sh`:

```bash
nano /home/tryawait.dev/webhook.sh
```

Wklej:

```bash
#!/bin/bash

REPO_PATH="/home/tryawait.dev/repository"
LOG_FILE="/home/tryawait.dev/logs/webhook.log"

log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

log_message "=== Webhook wywołany ==="

cd "$REPO_PATH" || exit 1

log_message "Wykonuję git fetch..."
git fetch origin main 2>&1 | tee -a "$LOG_FILE"

log_message "Wykonuję git reset --hard..."
git reset --hard origin/main 2>&1 | tee -a "$LOG_FILE"

COMMIT_HASH=$(git rev-parse --short HEAD)
COMMIT_MSG=$(git log -1 --pretty=%B)

log_message "✓ Deploy zakończony! Commit: $COMMIT_HASH - $COMMIT_MSG"

echo "OK"
```

Ustaw uprawnienia:

```bash
chmod +x /home/tryawait.dev/webhook.sh
```

### 5. Konfiguracja serwera WWW

#### Dla Apache (.htaccess)

Jeśli używasz Apache, utwórz `.htaccess` w katalogu z webhook.php:

```apache
<Files webhook.php>
    Order allow,deny
    Allow from all
</Files>
```

#### Dla Nginx

Dodaj do konfiguracji nginx:

```nginx
location /webhook.php {
    fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
}
```

### 6. Testuj lokalnie

```bash
# Test skryptu PHP
php /home/tryawait.dev/public_html/webhook.php

# Lub z poziomu web
curl https://tryawait.dev/webhook.php -X POST -d '{"ref":"refs/heads/main"}'
```

## ⚙️ Konfiguracja GitHub Webhook

### 1. Przejdź do ustawień repozytorium

https://github.com/DARK-YT/tryawait_dev/settings/hooks

### 2. Kliknij "Add webhook"

### 3. Wypełnij formularz:

- **Payload URL:** `https://tryawait.dev/webhook.php`
- **Content type:** `application/json`
- **Secret:** (wpisz ten sam secret co w `webhook.php` - `WEBHOOK_SECRET`)
- **SSL verification:** Enable
- **Which events would you like to trigger this webhook?**
  - Wybierz: "Just the push event"
- **Active:** ✓ (zaznaczone)

### 4. Kliknij "Add webhook"

## 🧪 Testowanie

### Test 1: Push do repozytorium

```bash
# Na swoim lokalnym komputerze
cd /workspace
echo "# Test" >> README.md
git add README.md
git commit -m "Test webhook"
git push github main
```

### Test 2: Sprawdź logi na serwerze

```bash
ssh uzytkownik@tryawait.dev
tail -f /home/tryawait.dev/logs/webhook.log
```

### Test 3: Sprawdź w GitHub

Przejdź do:
https://github.com/DARK-YT/tryawait_dev/settings/hooks

Kliknij na swój webhook i zobacz "Recent Deliveries" - powinien być status 200.

## 🔒 Bezpieczeństwo

### 1. Upewnij się, że webhook.php ma właściwe uprawnienia

```bash
chmod 755 /home/tryawait.dev/public_html/webhook.php
chown www-data:www-data /home/tryawait.dev/public_html/webhook.php
```

### 2. Upewnij się, że użytkownik www-data ma dostęp do repozytorium

```bash
# Dodaj www-data do grupy użytkownika lub ustaw odpowiednie uprawnienia
chgrp -R www-data /home/tryawait.dev/repository
chmod -R g+w /home/tryawait.dev/repository
```

### 3. Konfiguracja Git (ważne!)

```bash
# Przejdź do repozytorium
cd /home/tryawait.dev/repository

# Ustaw safe.directory (ważne dla bezpieczeństwa)
git config --global --add safe.directory /home/tryawait.dev/repository

# Ustaw credential helper, żeby nie pytał o hasło
git config credential.helper store
```

## 🐛 Rozwiązywanie problemów

### Problem: "Permission denied" podczas git pull

Rozwiązanie:
```bash
# Upewnij się, że www-data ma dostęp
sudo chown -R www-data:www-data /home/tryawait.dev/repository
sudo chmod -R 755 /home/tryawait.dev/repository
```

### Problem: Webhook nie jest wywoływany

1. Sprawdź logi GitHub (Settings → Webhooks → Recent Deliveries)
2. Sprawdź logi serwera: `tail -f /var/log/nginx/error.log`
3. Sprawdź czy URL jest dostępny: `curl -I https://tryawait.dev/webhook.php`

### Problem: "fatal: detected dubious ownership"

Rozwiązanie:
```bash
git config --global --add safe.directory /home/tryawait.dev/repository
```

## 📊 Monitorowanie

### Sprawdź ostatnie 50 linii logów:

```bash
tail -50 /home/tryawait.dev/logs/webhook.log
```

### Monitorowanie w czasie rzeczywistym:

```bash
tail -f /home/tryawait.dev/logs/webhook.log
```

### Rotacja logów (opcjonalnie):

Dodaj do `/etc/logrotate.d/webhook`:

```
/home/tryawait.dev/logs/webhook.log {
    weekly
    rotate 4
    compress
    missingok
    notifempty
}
```

## 🎉 Gotowe!

Teraz przy każdym push'u do brancha `main` kod na serwerze zostanie automatycznie zaktualizowany! 🚀
