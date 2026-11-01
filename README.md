# tryawait_dev 🚀

Repozytorium projektu tryawait.dev - automatycznie synchronizowane między Origin/Cursor a GitHub.

## 🌐 Deployment

Projekt jest hostowany na **smarthost** pod domeną **tryawait.dev**.

### Automatyczny Deploy przez GitHub Webhook

Po każdym push'u do brancha `main` kod jest automatycznie aktualizowany na serwerze dzięki webhook'owi GitHub.

**Dokumentacja instalacji:** Zobacz [WEBHOOK-INSTALLATION.md](./WEBHOOK-INSTALLATION.md)

## 📦 Struktura

- `webhook.php` - Skrypt PHP obsługujący webhook GitHub
- `webhook.sh` - Alternatywna wersja bash skryptu webhook
- `WEBHOOK-INSTALLATION.md` - Kompletna instrukcja instalacji i konfiguracji

## 🔗 Remote Repositories

- **GitHub:** https://github.com/DARK-YT/tryawait_dev
- **Origin/Cursor:** https://origin.cursor.com/git/dark-yt/tryawait_dev

## 🛠️ Jak to działa?

1. Robisz zmiany w kodzie i push'ujesz do `main`
2. GitHub wysyła webhook POST do `https://tryawait.dev/webhook.php`
3. Skrypt weryfikuje żądanie i wykonuje `git pull` na serwerze
4. Strona jest automatycznie zaktualizowana! 🎉

## 📝 Konfiguracja Webhook

Webhook jest skonfigurowany w GitHub:
- **URL:** https://tryawait.dev/webhook.php
- **Events:** Push to main branch
- **Secret:** Zabezpieczone kluczem

## 🔐 Bezpieczeństwo

- ✅ Weryfikacja podpisu HMAC SHA256
- ✅ Filtrowanie tylko push'ów do `main`
- ✅ Logowanie wszystkich żądań
- ✅ Bezpieczne zarządzanie credentials

## 📊 Monitorowanie

Logi deploy'ów znajdują się w:
```
/home/tryawait.dev/logs/webhook.log
```

## 👥 Organizacja

Projekt należy do organizacji **DARK-YT** na GitHub.
