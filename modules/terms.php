<?php
require_once __DIR__.'/../includes/auth_check.php';$page_title='Terms';include __DIR__.'/../includes/header.php';
?>
<main class="container information-page" id="main-content" tabindex="-1"><?= breadcrumbs($page_title) ?><h1>Terms</h1><p class="alert-kd alert-kd-warning" role="status">Owner review required. The final terms have not been published.</p><article class="card-kd"><div class="card-body-kd"><h2 class="h4">How the current service works</h2><p>Sellers publish asking prices and availability. Buyers and providers agree on pickup, delivery and service arrangements. Fulfillment and cash collection have separate records. The owner still needs to approve service responsibilities, cancellation/refund terms and dispute procedures.</p><a class="btn-kd btn-kd-primary" href="contact.php">Contact the platform</a></div></article></main>
<?php include __DIR__.'/../includes/footer.php'; ?>
