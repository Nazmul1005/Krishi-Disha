<?php
require_once __DIR__.'/../includes/auth_check.php';$page_title='Contact and support';include __DIR__.'/../includes/header.php';
?>
<main class="container information-page" id="main-content" tabindex="-1"><?= breadcrumbs($page_title) ?><h1>We’re here to help.</h1><p>For account access, provider review or marketplace questions, use the platform’s configured support contact.</p><article class="card-kd"><div class="card-body-kd"><h2 class="h4">Support contact</h2><p><?= e(env('SUPPORT_CONTACT','Contact the platform administrator')) ?></p><a class="btn-kd btn-kd-primary" href="support.php">Open a support request</a></div></article></main>
<?php include __DIR__.'/../includes/footer.php'; ?>
