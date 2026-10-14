<?php $bottomItems=[['href'=>dashboardUrl(),'label'=>'Dashboard','icon'=>'fa-house'],['href'=>'/KrishiDisha/user/marketplace.php','label'=>'Marketplace','icon'=>'fa-store'],['href'=>'/KrishiDisha/modules/notifications.php','label'=>'Notifications','icon'=>'fa-bell'],['href'=>'/KrishiDisha/modules/profile.php','label'=>'My Profile','icon'=>'fa-circle-user']]; ?>
<nav class="bottom-nav" aria-label="Mobile navigation">
<?php foreach($bottomItems as $item): ?><a href="<?= e($item['href']) ?>" <?= navigationActive($item['href'])?'aria-current="page"':'' ?>><?= icon($item['icon']) ?><span><?= t($item['label']) ?></span></a><?php endforeach; ?>
<button type="button" data-open-menu aria-label="Open navigation" aria-controls="sidebar" aria-expanded="false"><?= icon('fa-bars') ?><span><?= t('Menu','মেনু') ?></span></button>
</nav>
