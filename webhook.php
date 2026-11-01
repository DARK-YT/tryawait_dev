<?php
/**
 * GitHub Webhook - Auto Deploy Script
 * Domena: tryawait.dev
 * 
 * Umieść ten plik w katalogu dostępnym przez web (np. /home/tryawait.dev/public_html/webhook.php)
 * URL webhooka: https://tryawait.dev/webhook.php
 */

// ==================== KONFIGURACJA ====================

// Ścieżka do repozytorium na serwerze
define('REPO_PATH', '/home/tryawait.dev/repository');

// Secret z GitHub (ustaw taki sam w GitHub webhook settings)
define('WEBHOOK_SECRET', 'twoj_tajny_klucz_webhook_2024');

// Branch, który ma być deployowany
define('DEPLOY_BRANCH', 'refs/heads/main');

// Ścieżka do pliku z logami
define('LOG_FILE', '/home/tryawait.dev/logs/webhook.log');

// ==================== FUNKCJE ====================

function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] {$message}\n";
    
    // Zapisz do pliku (jeśli katalog istnieje)
    $logDir = dirname(LOG_FILE);
    if (is_dir($logDir) && is_writable($logDir)) {
        file_put_contents(LOG_FILE, $logEntry, FILE_APPEND);
    }
    
    // Wyślij również do error_log
    error_log($logEntry);
}

function verifySignature($payload, $signature) {
    if (empty($signature)) {
        return false;
    }
    
    $hash = 'sha256=' . hash_hmac('sha256', $payload, WEBHOOK_SECRET);
    return hash_equals($hash, $signature);
}

function executeCommand($command) {
    $output = [];
    $returnCode = 0;
    
    exec($command . ' 2>&1', $output, $returnCode);
    
    return [
        'success' => $returnCode === 0,
        'output' => implode("\n", $output),
        'code' => $returnCode
    ];
}

// ==================== GŁÓWNA LOGIKA ====================

// Pobierz payload
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

logMessage("=== Webhook wywołany ===");
logMessage("IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
logMessage("User-Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'));

// Weryfikacja podpisu (jeśli secret jest ustawiony)
if (!empty(WEBHOOK_SECRET)) {
    if (!verifySignature($payload, $signature)) {
        logMessage("BŁĄD: Nieprawidłowy podpis webhook!");
        http_response_code(403);
        die(json_encode(['error' => 'Invalid signature']));
    }
    logMessage("✓ Podpis zweryfikowany");
}

// Dekoduj payload
$data = json_decode($payload, true);

if ($data === null) {
    logMessage("BŁĄD: Nieprawidłowy JSON payload");
    http_response_code(400);
    die(json_encode(['error' => 'Invalid JSON']));
}

// Sprawdź czy to push do właściwego brancha
$ref = $data['ref'] ?? '';
logMessage("Event: push do {$ref}");

if ($ref !== DEPLOY_BRANCH) {
    logMessage("Ignoruję - to nie branch {DEPLOY_BRANCH}");
    die(json_encode([
        'message' => 'Ignored - not the deploy branch',
        'ref' => $ref
    ]));
}

// Sprawdź czy katalog repozytorium istnieje
if (!is_dir(REPO_PATH)) {
    logMessage("BŁĄD: Katalog repozytorium nie istnieje: " . REPO_PATH);
    http_response_code(500);
    die(json_encode(['error' => 'Repository path not found']));
}

// Przejdź do katalogu repozytorium i wykonaj git pull
logMessage("Rozpoczynam aktualizację...");

chdir(REPO_PATH);

// 1. Git fetch
logMessage("Wykonuję: git fetch origin main");
$result = executeCommand('git fetch origin main');
logMessage($result['output']);

if (!$result['success']) {
    logMessage("BŁĄD podczas git fetch (kod: {$result['code']})");
    http_response_code(500);
    die(json_encode(['error' => 'Git fetch failed', 'details' => $result['output']]));
}

// 2. Git reset --hard
logMessage("Wykonuję: git reset --hard origin/main");
$result = executeCommand('git reset --hard origin/main');
logMessage($result['output']);

if (!$result['success']) {
    logMessage("BŁĄD podczas git reset (kod: {$result['code']})");
    http_response_code(500);
    die(json_encode(['error' => 'Git reset failed', 'details' => $result['output']]));
}

// 3. Opcjonalnie: composer install (jeśli projekt PHP z composer)
if (file_exists(REPO_PATH . '/composer.json')) {
    logMessage("Wykonuję: composer install --no-dev --optimize-autoloader");
    $result = executeCommand('composer install --no-dev --optimize-autoloader');
    logMessage($result['output']);
}

// 4. Opcjonalnie: npm install (jeśli projekt Node.js)
if (file_exists(REPO_PATH . '/package.json')) {
    logMessage("Wykonuję: npm install --production");
    $result = executeCommand('npm install --production');
    logMessage($result['output']);
}

// Pobierz informacje o ostatnim commit
$commitHash = trim(shell_exec('git rev-parse --short HEAD'));
$commitMessage = trim(shell_exec('git log -1 --pretty=%B'));
$commitAuthor = trim(shell_exec('git log -1 --pretty=%an'));

logMessage("✓ Deploy zakończony pomyślnie!");
logMessage("Commit: {$commitHash} - {$commitMessage} (autor: {$commitAuthor})");

// Zwróć sukces
http_response_code(200);
echo json_encode([
    'success' => true,
    'message' => 'Deploy completed successfully',
    'commit' => $commitHash,
    'commit_message' => $commitMessage,
    'author' => $commitAuthor,
    'timestamp' => date('Y-m-d H:i:s')
]);
