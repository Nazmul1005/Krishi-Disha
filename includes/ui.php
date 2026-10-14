<?php
/** Shared presentation helpers. Business authorization remains in the existing services. */
function assetUrl(string $path): string {
    $file=dirname(__DIR__).'/'.$path;
    return '/KrishiDisha/'.$path.'?v='.(is_file($file) ? substr(hash_file('sha256',$file),0,12) : '1');
}
function dashboardUrl(?string $role=null): string {
    $role=$role ?? ($_SESSION['role'] ?? 'general');
    return '/KrishiDisha/'.($role==='general' ? 'user' : (in_array($role,['admin','farmer','dealer','tourist','cook','expert','guide'],true) ? $role : 'user')).'/dashboard.php';
}
function icon(string $name): string {
    $name=preg_match('/^fa-[a-z-]+$/D',$name) ? $name : 'fa-seedling';
    return '<i class="fa-solid '.e($name).'" aria-hidden="true"></i>';
}
function uiFieldId(): string {
    static $counter=0;return 'kd-field-'.(++$counter);
}
function uiEmpty(string $title,string $description,string $href='',string $action='Browse crops',string $symbol='fa-seedling'): string {
    return '<div class="empty-state">'.icon($symbol).'<h2 class="h5">'.e($title).'</h2><p>'.e($description).'</p>'.($href ? '<a class="btn-kd btn-kd-outline" href="'.e($href).'">'.e($action).'</a>' : '').'</div>';
}
function breadcrumbs(string $title): string {
    return '<nav class="breadcrumbs" aria-label="Breadcrumb"><a href="/KrishiDisha/">'.t('Home','হোম').'</a><span aria-hidden="true">/</span><span aria-current="page">'.e($title).'</span></nav>';
}
