<?php
$overview=dashboardData($pdo,(int)$_SESSION['user_id']);
$maxActivity=max(1,...array_column($overview['series'],'activity'));$maxMoney=max(1,...array_column($overview['series'],'money'));
$quickLinks=match($overview['user']['role']){
    'expert'=>[['/KrishiDisha/expert/sessions.php','Manage sessions'],['/KrishiDisha/modules/profile.php','Update availability']],
    'guide'=>[['/KrishiDisha/guide/bookings.php','View tour bookings'],['/KrishiDisha/guide/sessions.php','Manage consultations'],['/KrishiDisha/modules/profile.php','Update availability']],
    default=>[]
};
?>
<section class="dashboard-intro"><span class="eyebrow"><?= t('Your workspace','আপনার কাজের জায়গা') ?></span><h2><?= t('Welcome back','স্বাগতম') ?>, <?= e($overview['user']['name']) ?>.</h2><p><?= t('A clear view of your work, records and next steps.','আপনার কাজ, তথ্য এবং পরবর্তী পদক্ষেপ একসাথে।') ?></p></section>
<?php if($quickLinks): ?><nav class="dashboard-quick-actions" aria-label="Quick actions"><?php foreach($quickLinks as [$href,$label]): ?><a class="btn-kd btn-kd-outline" href="<?= e($href) ?>"><?= e($label) ?></a><?php endforeach; ?></nav><?php endif; ?>
<section class="attention-section" aria-labelledby="attention-title"><div class="section-heading"><h2 id="attention-title" class="h4">Needs your attention</h2><a class="text-link" href="/KrishiDisha/modules/payments.php">Payment records <?= icon('fa-arrow-right') ?></a></div><div class="attention-grid"><?php foreach($overview['attention'] as $item): ?><a class="attention-card" href="<?= e($item['href']) ?>"><?= icon($item['icon']) ?><strong><?= e($item['count']) ?></strong><span><?= e($item['title']) ?></span><small><?= $item['count']?'View and respond':'You’re up to date' ?> <?= icon('fa-arrow-right') ?></small></a><?php endforeach; ?></div></section>
<section class="dashboard-trends" aria-label="Six month activity and cash trends">
<?php foreach(['activity'=>[$overview['activityLabel'],(int)$overview['activityTotals']['total'],$maxActivity],'money'=>[$overview['moneyLabel'],taka($overview['moneyTotal']),$maxMoney]] as $key=>[$label,$total,$max]): ?>
<article class="card-kd"><div class="card-body-kd"><span class="eyebrow">Last six months</span><h2 class="h5"><?= e($label) ?></h2><strong class="trend-total"><?= e($total) ?></strong><span class="trend-note">All-time <?= $key==='money'?'verified records':'recorded activity' ?></span>
<div class="trend-chart" role="img" aria-label="<?= e($label) ?> over six months; values are available in the table below."><?php foreach($overview['series'] as $point): ?><div class="trend-column"><span><?= $key==='money'?taka($point[$key]):e($point[$key]) ?></span><div class="trend-bar" style="height:<?= round(($point[$key]/$max)*100,2) ?>%" data-value="<?= e($point[$key]) ?>"></div><small><?= e(date('M',strtotime($point['month'].'-01'))) ?></small></div><?php endforeach; ?></div>
<details class="chart-data"><summary>View monthly values</summary><table class="table-kd"><thead><tr><th>Month</th><th><?= e($label) ?></th></tr></thead><tbody><?php foreach($overview['series'] as $point): ?><tr><td><?= e($point['month']) ?></td><td><?= $key==='money'?taka($point[$key]):e($point[$key]) ?></td></tr><?php endforeach; ?></tbody></table></details>
<?php if(!array_sum(array_column($overview['series'],$key))): ?><p class="trend-note">No <?= $key==='money'?'verified cash':'activity' ?> recorded in this period.</p><?php endif; ?>
</div></article><?php endforeach; ?></section>
