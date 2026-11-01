# ⚡ Szybki Start - Instalacja Webhook na Serwerze

## 🎯 Cel

Automatyczny deploy na **tryawait.dev** po każdym push'u do `main`.

## 📋 Kroki na Serwerze

### 1. Zaloguj się przez SSH

```bash
ssh uzytkownik@tryawait.dev
```

### 2. Sklonuj repozytorium

```bash
# Utwórz katalog
mkdir -p /home/tryawait.dev/repository

# Sklonuj
cd /home/tryawait.dev/repository
git clone https://github.com/DARK-YT/tryawait_dev.git .
git checkout main
```

### 3. Utwórz katalog dla logów

```bash
mkdir -p /home/tryawait.dev/logs
chmod 755 /home/tryawait.dev/logs
```

### 4. Skopiuj webhook.php

```bash
# Skopiuj do public_html
cp /home/tryawait.dev/repository/webhook.php /home/tryawait.dev/public_html/webhook.php

# Ustaw uprawnienia
chmod 755 /home/tryawait.dev/public_html/webhook.php
```

### 5. Skonfiguruj webhook.php

```bash
nano /home/tryawait.dev/public_html/webhook.php
```

**ZMIEŃ TE LINIE:**

```php
define('REPO_PATH', '/home/tryawait.dev/repository');
define('WEBHOOK_SECRET', 'f72c776106629b0e0e87a20f023eebbd0fdc9f8460e8ba8e09e663c58b96faad');
define('LOG_FILE', '/home/tryawait.dev/logs/webhook.log');
```

⚠️ **SECRET** znajdziesz w pliku `.webhook-config` w sklonowanym repozytorium!

### 6. Ustaw uprawnienia Git

```bash
cd /home/tryawait.dev/repository

# Safe directory
git config --global --add safe.directory /home/tryawait.dev/repository

# Uprawnienia dla www-data
sudo chown -R www-data:www-data /home/tryawait.dev/repository
sudo chmod -R 755 /home/tryawait.dev/repository
```

### 7. Test

```bash
# Test lokalny
php /home/tryawait.dev/public_html/webhook.php

# Test przez curl (z innego terminala)
curl -X POST https://tryawait.dev/webhook.php \
  -H "Content-Type: application/json" \
  -d '{"ref":"refs/heads/main"}'
```

### 8. Sprawdź logi

```bash
tail -f /home/tryawait.dev/logs/webhook.log
```

## ✅ Gotowe!

Webhook GitHub jest już skonfigurowany i będzie wywoływał `https://tryawait.dev/webhook.php` przy każdym push'u.

## 🧪 Test End-to-End

1. Na swoim komputerze:
   ```bash
   cd /workspace
   echo "# Test" >> test.txt
   git add test.txt
   git commit -m "Test webhook"
   git push github main
   ```

2. Na serwerze sprawdź logi:
   ```bash
   tail -20 /home/tryawait.dev/logs/webhook.log
   ```

3. W GitHub sprawdź deliveries:
   https://github.com/DARK-YT/tryawait_dev/settings/hooks/705977622

## 🔍 Rozwiązywanie Problemów

### Problem: "Permission denied"

```bash
sudo chown -R www-data:www-data /home/tryawait.dev/repository
sudo chmod -R 755 /home/tryawait.dev/repository
```

### Problem: "dubious ownership"

```bash
git config --global --add safe.directory /home/tryawait.dev/repository
```

### Problem: Webhook nie odpowiada

1. Sprawdź czy plik jest dostępny:
   ```bash
   curl -I https://tryawait.dev/webhook.php
   ```

2. Sprawdź logi serwera:
   ```bash
   tail -f /var/log/nginx/error.log
   # lub
   tail -f /var/log/apache2/error.log
   ```

## 📚 Pełna Dokumentacja

Zobacz [WEBHOOK-INSTALLATION.md](./WEBHOOK-INSTALLATION.md) dla szczegółowej instrukcji.
