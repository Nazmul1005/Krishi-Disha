<?php
function base32Encode(string $bytes): string {
    $alphabet='ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; $bits=''; $out='';
    foreach (str_split($bytes) as $c) $bits.=str_pad(decbin(ord($c)),8,'0',STR_PAD_LEFT);
    foreach (str_split($bits,5) as $chunk) $out.=$alphabet[bindec(str_pad($chunk,5,'0'))];
    return $out;
}
function totp(string $secret, int $counter): string {
    $alphabet='ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; $bits=''; $key='';
    foreach (str_split($secret) as $c) { $index=strpos($alphabet,$c); if ($index === false) throw new InvalidArgumentException('Invalid MFA secret.'); $bits.=str_pad(decbin($index),5,'0',STR_PAD_LEFT); }
    foreach (str_split($bits,8) as $chunk) if(strlen($chunk)===8) $key.=chr(bindec($chunk));
    $hash=hash_hmac('sha1',pack('N2',0,$counter),$key,true); $offset=ord($hash[19])&15;
    $number=(unpack('N',substr($hash,$offset,4))[1]&0x7fffffff)%1000000;
    return str_pad((string)$number,6,'0',STR_PAD_LEFT);
}
function encryptSecret(string $raw): string {
    $nonce=random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    return base64_encode($nonce.sodium_crypto_secretbox($raw,$nonce,hash('sha256',APP_KEY,true)));
}
function decryptSecret(string $encrypted): string {
    $bytes=base64_decode($encrypted,true);
    if($bytes===false || strlen($bytes)<=SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) throw new RuntimeException('Invalid encrypted secret.');
    $raw=sodium_crypto_secretbox_open(substr($bytes,24),substr($bytes,0,24),hash('sha256',APP_KEY,true));
    if($raw===false) throw new RuntimeException('Unable to decrypt MFA secret.');
    return $raw;
}
function verifyTotp(PDO $pdo, array $user, string $code): bool {
    if(!preg_match('/^\d{6}$/D',$code)) return false;
    $secret=decryptSecret($user['mfa_secret']);
    for($counter=(int)floor(time()/30)-1;$counter<=(int)floor(time()/30)+1;$counter++) {
        if($counter>(int)$user['mfa_last_counter'] && hash_equals(totp($secret,$counter),$code)) {
            $stmt=$pdo->prepare('UPDATE USER SET mfa_last_counter=? WHERE id=? AND mfa_last_counter<?');
            $stmt->execute([$counter,$user['id'],$counter]); return $stmt->rowCount()===1;
        }
    }
    return false;
}
