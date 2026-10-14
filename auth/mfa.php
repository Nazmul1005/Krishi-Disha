<?php
require_once __DIR__.'/../includes/auth_check.php';
require_once __DIR__.'/../includes/mfa.php';
header('Cache-Control: no-store');
$pending=$_SESSION['mfa_pending'] ?? [];
if(($pending['expires'] ?? 0)<time()) { unset($_SESSION['mfa_pending'],$_SESSION['mfa_enroll']); header('Location: login.php');exit; }
$stmt=$pdo->prepare('SELECT * FROM USER WHERE id=?');$stmt->execute([$pending['id']]);$user=$stmt->fetch();
if(!$user || $user['status']!=='approved' || !(int)$user['email_verified'] || (int)$user['auth_version']!==(int)$pending['version']) { destroyLogin();header('Location: login.php');exit; }
$enrolling=empty($user['mfa_secret']);$error='';$recovery=[];
if($enrolling && empty($_SESSION['mfa_enroll'])) $_SESSION['mfa_enroll']=base32Encode(random_bytes(20));
if($_SERVER['REQUEST_METHOD']==='POST') {
    try {
        throttle($pdo,'mfa-user',(string)$user['id'],5);throttle($pdo,'mfa-ip',$_SERVER['REMOTE_ADDR'] ?? '',20);
        $code=textInput($_POST['code'] ?? '',100);
        if($enrolling) {
            $secret=$_SESSION['mfa_enroll'];$counter=(int)floor(time()/30);$valid=false;
            foreach([$counter-1,$counter,$counter+1] as $candidate) if(hash_equals(totp($secret,$candidate),$code)) { $valid=true;$counter=$candidate;break; }
            if(!$valid) throw new InvalidArgumentException('Invalid authentication code.');
            for($i=0;$i<8;$i++) $recovery[]=bin2hex(random_bytes(8));
            $hashes=array_map(fn($value)=>hash('sha256',$value),$recovery);
            $update=$pdo->prepare('UPDATE USER SET mfa_secret=?,mfa_last_counter=?,mfa_recovery=? WHERE id=? AND mfa_secret IS NULL AND auth_version=?');
            $update->execute([encryptSecret($secret),$counter,json_encode($hashes),$user['id'],$pending['version']]);
            if($update->rowCount()!==1) throw new RuntimeException('Enrollment changed. Please sign in again.');
            unset($_SESSION['mfa_enroll']);
            establishLogin($user);
        } else {
            $valid=verifyTotp($pdo,$user,$code);
            if(!$valid) $valid=transaction($pdo,function()use($pdo,$user,$code) {
                $stmt=$pdo->prepare('SELECT mfa_recovery FROM USER WHERE id=? FOR UPDATE');$stmt->execute([$user['id']]);$hashes=json_decode($stmt->fetchColumn() ?: '[]',true);
                $index=array_search(hash('sha256',$code),$hashes,true);if($index===false) return false;
                unset($hashes[$index]);$pdo->prepare('UPDATE USER SET mfa_recovery=? WHERE id=?')->execute([json_encode(array_values($hashes)),$user['id']]);return true;
            });
            if(!$valid) throw new InvalidArgumentException('Invalid authentication code.');
            establishLogin($user);redirectToDashboard();
        }
    } catch(Throwable $e) { $error=$e instanceof InvalidArgumentException ? $e->getMessage() : 'Unable to verify. Try again later.'; }
}
$page_title='Two-factor authentication';include __DIR__.'/../includes/header.php';
?>
<main id="main-content" tabindex="-1" class="auth-page"><div class="auth-card"><h1>Two-factor authentication</h1>
<?php if($error): ?><p role="alert"><?= e($error) ?></p><?php endif; ?>
<?php if($recovery): ?><p>Save these recovery codes privately. Each works once.</p><pre><?= e(implode("\n",$recovery)) ?></pre><a class="btn-kd btn-kd-primary" href="../admin/dashboard.php">Continue</a>
<?php else: ?>
<?php if($enrolling): ?><p>Add this secret to an authenticator app, then enter its six-digit code.</p><code><?= e($_SESSION['mfa_enroll']) ?></code><p>Account: <?= e($user['email']) ?> · Issuer: KrishiDisha</p><?php endif; ?>
<form method="POST"><?= csrfField() ?><label for="code">Authentication or recovery code</label><input class="form-control" id="code" name="code" autocomplete="one-time-code" required><button class="btn-kd btn-kd-primary">Verify</button></form>
<?php endif; ?></div></main><?php include __DIR__.'/../includes/footer.php'; ?>
