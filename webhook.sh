#!/bin/bash

###############################################################################
# GitHub Webhook Handler - Bash Version
# Domena: tryawait.dev
#
# Użycie przez CGI:
# 1. Umieść w /home/tryawait.dev/cgi-bin/webhook.sh
# 2. chmod +x webhook.sh
# 3. Skonfiguruj serwer web do obsługi CGI
###############################################################################

# ==================== KONFIGURACJA ====================

REPO_PATH="/home/tryawait.dev/repository"
LOG_FILE="/home/tryawait.dev/logs/webhook.log"
WEBHOOK_SECRET="twoj_tajny_klucz_webhook_2024"
DEPLOY_BRANCH="refs/heads/main"

# ==================== FUNKCJE ====================

log_message() {
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    echo "[$timestamp] $1" >> "$LOG_FILE"
}

verify_signature() {
    local payload="$1"
    local signature="$2"
    
    if [ -z "$signature" ]; then
        return 1
    fi
    
    local expected="sha256=$(echo -n "$payload" | openssl dgst -sha256 -hmac "$WEBHOOK_SECRET" | sed 's/^.* //')"
    
    if [ "$signature" = "$expected" ]; then
        return 0
    else
        return 1
    fi
}

# ==================== GŁÓWNA LOGIKA ====================

# Nagłówki HTTP
echo "Content-Type: application/json"
echo ""

# Odczytaj payload z stdin
if [ "$REQUEST_METHOD" = "POST" ]; then
    PAYLOAD=$(cat)
else
    echo '{"error":"Method not allowed"}'
    exit 1
fi

log_message "=== Webhook wywołany ==="
log_message "IP: ${REMOTE_ADDR:-unknown}"
log_message "User-Agent: ${HTTP_USER_AGENT:-unknown}"

# Weryfikacja podpisu (jeśli secret jest ustawiony)
if [ -n "$WEBHOOK_SECRET" ]; then
    SIGNATURE="$HTTP_X_HUB_SIGNATURE_256"
    
    if ! verify_signature "$PAYLOAD" "$SIGNATURE"; then
        log_message "BŁĄD: Nieprawidłowy podpis webhook!"
        echo '{"error":"Invalid signature"}'
        exit 1
    fi
    log_message "✓ Podpis zweryfikowany"
fi

# Sprawdź ref w payload
REF=$(echo "$PAYLOAD" | grep -o '"ref":"[^"]*"' | cut -d'"' -f4)
log_message "Event: push do $REF"

if [ "$REF" != "$DEPLOY_BRANCH" ]; then
    log_message "Ignoruję - to nie branch $DEPLOY_BRANCH"
    echo "{\"message\":\"Ignored - not the deploy branch\",\"ref\":\"$REF\"}"
    exit 0
fi

# Sprawdź czy katalog istnieje
if [ ! -d "$REPO_PATH" ]; then
    log_message "BŁĄD: Katalog repozytorium nie istnieje: $REPO_PATH"
    echo '{"error":"Repository path not found"}'
    exit 1
fi

# Przejdź do katalogu repozytorium
cd "$REPO_PATH" || {
    log_message "BŁĄD: Nie można przejść do katalogu $REPO_PATH"
    echo '{"error":"Cannot change to repository directory"}'
    exit 1
}

# Wykonaj git fetch
log_message "Wykonuję: git fetch origin main"
if ! git fetch origin main >> "$LOG_FILE" 2>&1; then
    log_message "BŁĄD podczas git fetch"
    echo '{"error":"Git fetch failed"}'
    exit 1
fi

# Wykonaj git reset --hard
log_message "Wykonuję: git reset --hard origin/main"
if ! git reset --hard origin/main >> "$LOG_FILE" 2>&1; then
    log_message "BŁĄD podczas git reset"
    echo '{"error":"Git reset failed"}'
    exit 1
fi

# Opcjonalnie: composer install (jeśli istnieje composer.json)
if [ -f "$REPO_PATH/composer.json" ]; then
    log_message "Wykonuję: composer install"
    composer install --no-dev --optimize-autoloader >> "$LOG_FILE" 2>&1
fi

# Opcjonalnie: npm install (jeśli istnieje package.json)
if [ -f "$REPO_PATH/package.json" ]; then
    log_message "Wykonuję: npm install"
    npm install --production >> "$LOG_FILE" 2>&1
fi

# Pobierz informacje o commit
COMMIT_HASH=$(git rev-parse --short HEAD)
COMMIT_MSG=$(git log -1 --pretty=%B)
COMMIT_AUTHOR=$(git log -1 --pretty=%an)

log_message "✓ Deploy zakończony pomyślnie!"
log_message "Commit: $COMMIT_HASH - $COMMIT_MSG (autor: $COMMIT_AUTHOR)"

# Zwróć sukces
echo "{\"success\":true,\"message\":\"Deploy completed successfully\",\"commit\":\"$COMMIT_HASH\",\"commit_message\":\"$COMMIT_MSG\",\"author\":\"$COMMIT_AUTHOR\",\"timestamp\":\"$(date '+%Y-%m-%d %H:%M:%S')\"}"

exit 0
