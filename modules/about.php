<?php
require_once __DIR__.'/../includes/auth_check.php';$page_title='About KrishiDisha';include __DIR__.'/../includes/header.php';
?>
<main class="container information-page" id="main-content" tabindex="-1"><?= breadcrumbs($page_title) ?><h1>A place to connect around agriculture.</h1><p>KrishiDisha connects produce listings, farm visits, local food and scheduled consultations with reviewed agricultural references in Bangladesh.</p><article class="card-kd"><div class="card-body-kd"><h2 class="h4">Clear records, local context</h2><p>Produce orders reserve stock. Fulfillment and actual cash collection are recorded separately. Agricultural guidance is published after source review; check its source and date and seek qualified local advice.</p><a class="btn-kd btn-kd-primary" href="contact.php">Contact the platform</a></div></article></main>
<?php include __DIR__.'/../includes/footer.php'; ?>
