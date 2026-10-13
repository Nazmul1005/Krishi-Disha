<?php
/**
 * KrishiDisha — Shared helper utilities
 * CSRF protection, flash messages, and small view helpers.
 *
 * Assumes a session has already been started (see auth_check.php).
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/uploads.php';
require_once __DIR__ . '/i18n.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ============================================================
   CSRF PROTECTION
   ============================================================ */

/** Return the current CSRF token, creating one if needed. */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Render a hidden CSRF input for use inside <form> tags. */
function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken()) . '">';
}

/**
 * Validate the CSRF token from a POST request.
 * On failure, redirects back with an error flash (or dies for non-GET/POST).
 */
function verifyCsrf(): void
{
    $sent = $_POST['csrf_token'] ?? '';
    if (!is_string($sent) || $sent === '' || !hash_equals($_SESSION['csrf_token'] ?? '', $sent)) {
        http_response_code(403);
        die('Security check failed. Reload the form and try again.');
        exit;
    }
}

/* ============================================================
   FLASH MESSAGES
   ============================================================ */

/** Queue a one-time flash message. Types: success | error | warning | info */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/** Render (and clear) all queued flash messages as styled alerts. */
function renderFlash(): string
{
    if (empty($_SESSION['flash'])) {
        return '';
    }
    $map = [
        'success' => ['alert-kd-success', 'fa-circle-check'],
        'error'   => ['alert-kd-error',   'fa-circle-exclamation'],
        'warning' => ['alert-kd-warning', 'fa-triangle-exclamation'],
        'info'    => ['alert-kd-info',     'fa-circle-info'],
    ];
    $html = '';
    foreach ($_SESSION['flash'] as $f) {
        [$cls, $icon] = $map[$f['type']] ?? $map['info'];
        $html .= '<div class="alert-kd ' . $cls . '" data-autohide="5000">'
               . '<i class="fa-solid ' . $icon . '"></i> '
               . htmlspecialchars($f['message']) . '</div>';
    }
    unset($_SESSION['flash']);
    return $html;
}

/* ============================================================
   SMALL VIEW HELPERS
   ============================================================ */

/** Escape a value for safe HTML output. */
function e($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Format an amount as Bangladeshi Taka. */
function taka($amount): string
{
    return '৳ ' . number_format((float)$amount, 2);
}

/**
 * Render a styled empty-state block for lists/tables with no data.
 */
function emptyState(string $title, string $subtitle = '', string $icon = '📭'): string
{
    return '<div style="text-align:center;padding:60px 20px;color:var(--text-muted);">'
         . '<div style="font-size:56px;margin-bottom:14px;">' . $icon . '</div>'
         . '<div style="font-size:17px;font-weight:700;color:var(--text);">' . e($title) . '</div>'
         . ($subtitle ? '<div style="font-size:14px;margin-top:6px;">' . e($subtitle) . '</div>' : '')
         . '</div>';
}

/* ============================================================
   AUTH TOKENS (password reset + email verification)
   ============================================================ */

/**
 * Create a single-use auth token, store only its hash, and return the raw token.
 * @param string $type 'password_reset' | 'email_verify'
 */
function createAuthToken(PDO $pdo, int $userId, string $type, int $ttlMinutes = 60): string
{
    if(!$pdo->inTransaction()) throw new LogicException('Token creation requires a transaction.');
    $lock=$pdo->prepare('SELECT id FROM USER WHERE id=? FOR UPDATE');$lock->execute([$userId]);if(!$lock->fetchColumn()) throw new InvalidArgumentException('Account not found.');
    $raw  = bin2hex(random_bytes(32));
    $hash = hash('sha256', $raw);
    // Invalidate any previous unused tokens of the same type for this user.
    $pdo->prepare("UPDATE AUTH_TOKEN SET used = 1 WHERE user_id = ? AND type = ? AND used = 0")
        ->execute([$userId, $type]);
    $pdo->prepare("INSERT INTO AUTH_TOKEN (user_id, token_hash, type, expires_at)
                   VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE))")
        ->execute([$userId, $hash, $type, $ttlMinutes]);
    return $raw;
}

/**
 * Validate + consume a token. Returns the user_id on success, or null.
 * Marks the token used so it cannot be replayed.
 */
function consumeAuthToken(PDO $pdo, string $rawToken, string $type): ?int
{
    if (!preg_match('/^[a-f0-9]{64}$/D', $rawToken)) return null;
    if (!$pdo->inTransaction()) throw new LogicException('Token consumption requires a transaction.');
    $stmt=$pdo->prepare('SELECT id,user_id FROM AUTH_TOKEN WHERE token_hash=? AND type=? AND used=0 AND expires_at>NOW() FOR UPDATE');
    $stmt->execute([hash('sha256',$rawToken),$type]);
    $row=$stmt->fetch();
    if (!$row) return null;
    $pdo->prepare('UPDATE AUTH_TOKEN SET used=1 WHERE id=? AND used=0')->execute([$row['id']]);
    return (int)$row['user_id'];
}

/** Build an absolute base URL (scheme://host) for links in emails/messages. */
function baseUrl(): string
{
    return APP_URL;
}
