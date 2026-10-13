<?php
require_once __DIR__.'/../includes/auth_check.php';requireAuth(['admin']);require_once __DIR__.'/../includes/content.php';
$section=$_GET['section'] ?? 'nutrients';if(!in_array($section,['nutrients','retention','regions'],true)) {http_response_code(400);exit;}
if($_SERVER['REQUEST_METHOD']==='POST') {
    try {
        $source=sourceUrl($_POST['source_url'] ?? '');if(($_POST['review_ack'] ?? '')!=='1') throw new InvalidArgumentException('Confirm qualified review before publishing.');
        $crop=(int)($_POST['crop_id'] ?? 0);
        transaction($pdo,function()use($pdo,$section,$source,$crop) {
            if($section==='nutrients') {
                $raw=$_POST['amount'] ?? '';if(!is_string($raw) || !preg_match('/^\d{1,6}(\.\d{1,4})?$/D',$raw)) throw new InvalidArgumentException('Enter a nonnegative amount with at most four decimal places.');
                $pdo->prepare("INSERT INTO CROP_VITAMIN(crop_id,vitamin_id,amount_per_100g,source_url,reviewed_by,reviewed_at,review_status) VALUES (?,?,?,?,?,NOW(),'reviewed') ON DUPLICATE KEY UPDATE amount_per_100g=VALUES(amount_per_100g),source_url=VALUES(source_url),reviewed_by=VALUES(reviewed_by),reviewed_at=NOW(),review_status='reviewed'")->execute([$crop,(int)($_POST['vitamin_id'] ?? 0),$raw,$source,$_SESSION['user_id']]);
                audit($pdo,'nutrition.reviewed','CROP_VITAMIN',$crop,['vitamin_id'=>(int)$_POST['vitamin_id'],'source'=>$source]);
            } elseif($section==='retention') {
                validateProposal('nutrition',$_POST);$notes=textInput($_POST['preparation_notes'] ?? '',3000);
                $pdo->prepare("INSERT INTO NUTRIENT_RETENTION(crop_id,vitamin_id,method_id,retention_percentage,preparation_notes,source_url,reviewed_by,reviewed_at,review_status) VALUES (?,?,?,?,?,?,?,NOW(),'reviewed')")->execute([$crop,(int)$_POST['vitamin_id'],(int)$_POST['method_id'],$_POST['retention_percentage'],$notes,$source,$_SESSION['user_id']]);audit($pdo,'retention.reviewed','NUTRIENT_RETENTION',(int)$pdo->lastInsertId());
            } else {
                validateProposal('recommender',$_POST);
                $pdo->prepare("INSERT INTO REGION_CROP(crop_id,region,soil_type,season,suitability_score,notes,source_url,reviewed_by,reviewed_at,review_status) VALUES (?,?,?,?,?,?,?,?,NOW(),'reviewed')")->execute([$crop,textInput($_POST['region'],150),textInput($_POST['soil_type'],100),$_POST['season'],(int)$_POST['suitability_score'],textInput($_POST['notes'] ?? '',3000),$source,$_SESSION['user_id']]);audit($pdo,'suitability.reviewed','REGION_CROP',(int)$pdo->lastInsertId());
            }
        });flash('success','Reviewed reference saved.');
    }catch(Throwable $e) {flash('error',$e instanceof InvalidArgumentException?$e->getMessage():'Reference could not be saved.');}
    header('Location: knowledge.php?section='.$section);exit;
}
$crops=$pdo->query('SELECT id,name FROM CROP ORDER BY name')->fetchAll();$vitamins=$pdo->query('SELECT * FROM VITAMIN ORDER BY name')->fetchAll();$methods=$pdo->query('SELECT id,name FROM COOKING_METHOD')->fetchAll();$page_title='Review agricultural references';include __DIR__.'/../includes/header.php';
?>
<div class="layout-wrapper"><?php include __DIR__.'/../includes/sidebar.php'; ?><main class="main-content" id="main-content" tabindex="-1"><div class="page-body"><h1>Review agricultural references</h1><?= renderFlash() ?><p>Use a credible source and qualified local review. Never infer missing values. Nutrient amounts use the edible raw portion per 100 g; check vitamin A units explicitly. Retention needs preparation conditions. Suitability scores are reference judgments, not measured predictions.</p>
<nav><a href="?section=nutrients">Raw nutrient amounts</a> · <a href="?section=retention">Cooking retention</a> · <a href="?section=regions">Regional suitability</a></nav>
<form method="POST" class="form-kd card-kd mt-3"><div class="card-body-kd"><?= csrfField() ?><label>Crop</label><select class="form-control" name="crop_id" required><?php foreach($crops as $crop): ?><option value="<?= e($crop['id']) ?>"><?= e($crop['name']) ?></option><?php endforeach; ?></select>
<?php if($section!=='regions'): ?><label>Nutrient and unit</label><select class="form-control" name="vitamin_id" required><?php foreach($vitamins as $vitamin): ?><option value="<?= e($vitamin['id']) ?>"><?= e($vitamin['name']) ?> (<?= e($vitamin['unit']) ?>)</option><?php endforeach; ?></select><?php endif; ?>
<?php if($section==='nutrients'): ?><label>Amount per 100 g raw edible portion</label><input class="form-control" type="number" name="amount" min="0" step="0.0001" required>
<?php elseif($section==='retention'): ?><label>Method</label><select class="form-control" name="method_id"><?php foreach($methods as $method): ?><option value="<?= e($method['id']) ?>"><?= e($method['name']) ?></option><?php endforeach; ?></select><label>Retention percentage</label><input class="form-control" type="number" name="retention_percentage" min="0" max="100" step="0.01" required><label>Preparation conditions, time, water and source basis</label><textarea class="form-control" name="preparation_notes" required maxlength="3000"></textarea>
<?php else: ?><label>District or agroecological area (specify which)</label><input class="form-control" name="region" maxlength="150" required><label>Soil type</label><input class="form-control" name="soil_type" maxlength="100" required><label>Season</label><select class="form-control" name="season"><?php foreach(['summer','winter','rainy','all'] as $season): ?><option><?= e($season) ?></option><?php endforeach; ?></select><label>Reviewed suitability score (1–10)</label><input class="form-control" name="suitability_score" type="number" min="1" max="10" required><label>Variety, planting window, irrigation and risk context</label><textarea class="form-control" name="notes" maxlength="3000" required></textarea><?php endif; ?>
<label>Reference source URL</label><input class="form-control" name="source_url" type="url" maxlength="500" required><label><input type="checkbox" name="review_ack" value="1" required> I checked the source, units, preparation/local context and obtained qualified review.</label><button class="btn-kd btn-kd-primary">Publish reviewed reference</button></div></form></div></main></div><?php include __DIR__.'/../includes/footer.php'; ?>
