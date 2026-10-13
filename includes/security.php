<?php
function destroyLogin(): void {
    $language=$_SESSION['language'] ?? 'bn';
    $_SESSION = ['language'=>$language];
    session_regenerate_id(true);
}

function positiveDecimal($raw, int $max = 1000000): string {
    if (!is_scalar($raw) || !preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D', (string)$raw) || (float)$raw <= 0 || (float)$raw > $max) {
        throw new InvalidArgumentException('Enter a positive number with at most two decimal places.');
    }
    return number_format((float)$raw, 2, '.', '');
}
function textInput($raw, int $max = 255, bool $required = true): string {
    if (!is_string($raw)) throw new InvalidArgumentException('Invalid text input.');
    $value = trim($raw);
    if (($required && $value === '') || mb_strlen($value) > $max) throw new InvalidArgumentException('Check the required fields and their length.');
    return $value;
}
function futureDate($raw): string {
    if (!is_string($raw)) throw new InvalidArgumentException('Invalid date.');
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $raw);
    if (!$date || $date->format('Y-m-d') !== $raw || $raw < date('Y-m-d')) throw new InvalidArgumentException('Choose today or a future date.');
    return $raw;
}
function throttle(PDO $pdo, string $action, string $identity, int $limit = 10, int $seconds = 900): void {
    $key = hash_hmac('sha256', $action . ':' . strtolower($identity), APP_KEY);
    $pdo->prepare('INSERT INTO RATE_LIMIT (bucket, attempts, window_start) VALUES (?,1,NOW()) ON DUPLICATE KEY UPDATE attempts=IF(window_start < DATE_SUB(NOW(), INTERVAL ? SECOND),1,attempts+1), window_start=IF(window_start < DATE_SUB(NOW(), INTERVAL ? SECOND),NOW(),window_start)')->execute([$key,$seconds,$seconds]);
    $stmt=$pdo->prepare('SELECT attempts FROM RATE_LIMIT WHERE bucket=?'); $stmt->execute([$key]);
    if ((int)$stmt->fetchColumn() > $limit) {
        http_response_code(429);
        header('Retry-After: ' . $seconds);
        throw new RuntimeException('Too many attempts. Please try again later.');
    }
}
function requestKeyField(): string {
    return '<input type="hidden" name="request_key" value="' . bin2hex(random_bytes(16)) . '">';
}
function requestKey(): string {
    $key=$_POST['request_key'] ?? '';
    if (!is_string($key) || !preg_match('/^[a-f0-9]{32}$/D', $key)) throw new InvalidArgumentException('Reload the form and try again.');
    return $key;
}
function transaction(PDO $pdo, callable $operation) {
    $pdo->beginTransaction();
    try { $result=$operation(); $pdo->commit(); return $result; }
    catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
}
function audit(PDO $pdo, string $action, string $entity, int $id, array $details=[]): void {
    $pdo->prepare('INSERT INTO AUDIT_LOG (actor_id,action,entity,entity_id,details) VALUES (?,?,?,?,?)')->execute([$_SESSION['user_id'] ?? null,$action,$entity,$id,json_encode($details,JSON_THROW_ON_ERROR)]);
}
function actionButton(string $name, int $id, string $label, string $class='btn-kd btn-kd-outline', string $action=''): string {
    return '<form method="POST" action="'.e($action).'" style="display:inline">'.csrfField().'<button class="'.e($class).'" name="'.e($name).'" value="'.$id.'">'.e($label).'</button></form>';
}
