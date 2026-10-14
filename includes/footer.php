<?php if(!empty($GLOBALS['kd_lists'])): ?><div class="list-pagination-area <?= $roleLayout?'role-pagination':'' ?>"><?= listPagers() ?></div><?php endif; ?>
<?php include __DIR__.'/site_footer.php'; ?>
<?php if($roleLayout): include __DIR__.'/bottom_nav.php'; endif; ?>
<dialog id="confirmDialog" class="kd-dialog" aria-labelledby="confirmTitle" aria-describedby="confirmMessage">
<form method="dialog"><h2 id="confirmTitle"><?= t('Confirm action','কাজটি নিশ্চিত করুন') ?></h2><p id="confirmMessage"></p><div class="dialog-actions"><button class="btn-kd btn-kd-outline" value="cancel"><?= t('Cancel','বাতিল') ?></button><button class="btn-kd btn-kd-primary" value="confirm"><?= t('Confirm','নিশ্চিত করুন') ?></button></div></form>
</dialog>
<div id="pageLoading" class="page-loading" hidden role="status"><span class="visually-hidden">Loading the next page</span><div class="skeleton"></div><div class="skeleton"></div><div class="skeleton"></div></div>
<script src="<?= e(assetUrl('assets/vendor/bootstrap/bootstrap.bundle.min.js')) ?>"></script>
<script src="<?= e(assetUrl('assets/js/main.js')) ?>"></script>
</body></html>
