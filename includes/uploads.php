<?php
function saveImage(string $field,bool $approved=false): ?string {
    global $pdo;
    $file=$_FILES[$field] ?? null;
    if(!$file || $file['error']===UPLOAD_ERR_NO_FILE) return null;
    if($file['error']!==UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name']) || $file['size']>5*1024*1024) throw new InvalidArgumentException('Upload a JPG, PNG or WebP image up to 5 MB.');
    throttle($pdo,'uploads-user',(string)$_SESSION['user_id'],20,86400);
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if(!in_array($mime,['image/jpeg','image/png','image/webp'],true)) throw new InvalidArgumentException('Only decoded JPG, PNG or WebP images are accepted.');
    $size=getimagesize($file['tmp_name']);
    if(!$size || $size[0]<1 || $size[1]<1 || $size[0]*$size[1]>4000000) throw new InvalidArgumentException('Image dimensions must total at most four million pixels.');
    $stmt=$pdo->prepare('SELECT COUNT(*) FROM MEDIA WHERE owner_id=? AND created_at>DATE_SUB(NOW(), INTERVAL 1 DAY)');$stmt->execute([$_SESSION['user_id']]);
    if((int)$stmt->fetchColumn()>=20) throw new InvalidArgumentException('Daily image upload limit reached.');
    $bytes=file_get_contents($file['tmp_name']);$image=imagecreatefromstring($bytes);
    if(!$image) throw new InvalidArgumentException('Unable to decode the image.');
    $id=bin2hex(random_bytes(16));$dir=STORAGE_PATH.'/media';if(!is_dir($dir) && !mkdir($dir,0700,true)) throw new RuntimeException('Media storage unavailable.');
    $destination=$dir.'/'.$id.'.jpg';
    try {
        if(!imagejpeg($image,$destination,85)) throw new RuntimeException('Unable to save image.');
        chmod($destination,0600);
        $pdo->prepare('INSERT INTO MEDIA(id,owner_id,mime,status) VALUES (?,?,?,?)')->execute([$id,$_SESSION['user_id'],'image/jpeg',$approved?'approved':'pending']);
    } catch(Throwable $e) { if(is_file($destination)) unlink($destination);throw $e; }
    finally { imagedestroy($image); }
    return 'media.php?id='.$id;
}
function mediaAvailable($path): bool {
    return is_string($path) && (preg_match('/^media\.php\?id=[a-f0-9]{32}$/D',$path)===1 || (str_starts_with($path,'assets/images/') && is_file(dirname(__DIR__).'/'.$path)));
}
