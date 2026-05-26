<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
requireAuth(['admin']);

$msg = ''; $err = '';
$filter = $_GET['filter'] ?? 'pending';

// ─── Actions ─────────────────────────────────────────────────────────────────
if (isset($_GET['approve'])) {
    $id  = (int)$_GET['approve'];
    $row = $pdo->prepare("SELECT * FROM DATA_PROPOSAL WHERE id=?"); $row->execute([$id]); $row = $row->fetch();
    if ($row) {
        $data = json_decode($row['proposed_data'], true);
        try {
            $pdo->beginTransaction();
            if ($row['section'] === 'crop') {
                $pdo->prepare("INSERT INTO CROP (name,scientific_name,local_name,origin,history,trade_status,season,category,image)
                               VALUES (?,?,?,?,?,?,?,?,?)")
                    ->execute([
                        $data['name']??'', $data['scientific_name']??'', $data['local_name']??'',
                        $data['origin']??'', $data['history']??'', $data['trade_status']??'local',
                        $data['season']??'all', $data['category']??'', $data['image']??null
                    ]);
            } elseif ($row['section'] === 'disease') {
                $pdo->prepare("INSERT INTO DISEASE (name,symptoms,solution,affected_part,image) VALUES (?,?,?,?,?)")
                    ->execute([$data['name']??'', $data['symptoms']??'', $data['solution']??'', $data['affected_part']??'', $data['image']??null]);
                $did = $pdo->lastInsertId();
                // Link crops by name if provided
                if (!empty($data['crop_names'])) {
                    foreach (explode(',', $data['crop_names']) as $cname) {
                        $cf = $pdo->prepare("SELECT id FROM CROP WHERE name=?"); $cf->execute([trim($cname)]); $cf=$cf->fetch();
                        if ($cf) $pdo->prepare("INSERT IGNORE INTO CROP_DISEASE (crop_id,disease_id) VALUES (?,?)")->execute([$cf['id'], $did]);
                    }
                }
            } elseif ($row['section'] === 'recommender') {
                $pdo->prepare("INSERT INTO REGION_CROP (crop_id,region,soil_type,season,suitability_score) VALUES (?,?,?,?,?)")
                    ->execute([$data['crop_id']??0, $data['region']??'', $data['soil_type']??'', $data['season']??'all', $data['suitability_score']??5]);
            } elseif ($row['section'] === 'nutrition') {
                $pdo->prepare("INSERT INTO CROP_VITAMIN (crop_id,vitamin_id,method_id,retention_percentage) VALUES (?,?,?,?)")
                    ->execute([$data['crop_id']??0, $data['vitamin_id']??0, $data['method_id']??0, $data['retention_percentage']??0]);
            }
            $pdo->prepare("UPDATE DATA_PROPOSAL SET status='approved', reviewed_by=?, reviewed_at=NOW() WHERE id=?")
                ->execute([$_SESSION['user_id'], $id]);
            $pdo->commit();
            // Notify user (optional placeholder)
            $msg = 'Proposal approved and data added to the platform!';
        } catch (Exception $e) {
            $pdo->rollBack();
            $err = 'Error: ' . $e->getMessage();
        }
    }
    header('Location: proposals.php?filter='.$filter.'&msg='.urlencode($msg).'&err='.urlencode($err));
    exit;
}

if (isset($_GET['reject'])) {
    $id = (int)$_GET['reject'];
    $reason = urldecode($_GET['reason'] ?? '');
    $pdo->prepare("UPDATE DATA_PROPOSAL SET status='rejected', reviewed_by=?, reviewed_at=NOW(), admin_notes=? WHERE id=?")
        ->execute([$_SESSION['user_id'], $reason, $id]);
    header('Location: proposals.php?filter='.$filter.'&msg='.urlencode('Proposal rejected.')); exit;
}

if (isset($_GET['msg'])) $msg = htmlspecialchars(urldecode($_GET['msg']));
if (isset($_GET['err'])) $err = htmlspecialchars(urldecode($_GET['err']));

$proposals = $pdo->prepare("
    SELECT dp.*, u.name as user_name, u.email as user_email, u.role as user_role
    FROM DATA_PROPOSAL dp JOIN USER u ON dp.user_id=u.id
    WHERE (:filter='all' OR dp.status=:filter2)
    ORDER BY dp.created_at DESC
");
$proposals->execute([':filter'=>$filter, ':filter2'=>$filter]);
$proposals = $proposals->fetchAll();

$counts = $pdo->query("SELECT status, COUNT(*) as cnt FROM DATA_PROPOSAL GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
$total  = array_sum($counts);

$page_title = 'Content Proposals';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="layout-wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">

<div class="topbar">
    <div class="d-flex align-items-center gap-3">
        <button id="sidebarToggle" class="btn btn-sm d-lg-none" style="border:none;font-size:20px;"><i class="fa-solid fa-bars"></i></button>
        <div class="topbar-title"><i class="fa-solid fa-inbox me-2" style="color:var(--primary);"></i>Content Proposals</div>
    </div>
    <div class="topbar-actions">
        <a href="manage_content.php" class="btn-kd btn-kd-outline" style="padding:6px 14px;font-size:12px;"><i class="fa-solid fa-pen-to-square"></i> Manage Content</a>
    </div>
</div>

<div class="page-body">
<?php if ($msg): ?><div class="alert-kd alert-kd-success" data-autohide="5000"><i class="fa-solid fa-check-circle"></i> <?= $msg ?></div><?php endif; ?>
<?php if ($err):  ?><div class="alert-kd alert-kd-error"><i class="fa-solid fa-exclamation-circle"></i> <?= $err ?></div><?php endif; ?>

<!-- Stats & Filter -->
<div style="display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap;align-items:center;">
    <?php $filters = ['all'=>['text-muted','All'],'pending'=>['badge-warning','Pending'],'approved'=>['badge-success','Approved'],'rejected'=>['badge-danger','Rejected']]; ?>
    <?php foreach ($filters as $key => [$cls, $label]): ?>
    <a href="?filter=<?= $key ?>" style="text-decoration:none;">
        <div style="padding:8px 16px;border-radius:20px;font-size:13px;font-weight:700;
            background:<?= $filter===$key ? 'linear-gradient(135deg,var(--primary),var(--primary-light))' : 'var(--surface3)' ?>;
            color:<?= $filter===$key ? '#fff' : 'var(--text)' ?>;">
            <?= $label ?>
            <span style="background:rgba(255,255,255,0.25);border-radius:10px;padding:2px 8px;margin-left:6px;font-size:11px;">
                <?= $counts[$key] ?? 0 ?>
            </span>
        </div>
    </a>
    <?php endforeach; ?>
    <div style="margin-left:auto;font-size:12px;color:var(--text-muted);"><?= count($proposals) ?> proposals shown</div>
</div>

<!-- Proposal List -->
<?php if (!$proposals): ?>
<div style="text-align:center;padding:60px;color:var(--text-muted);">
    <div style="font-size:60px;margin-bottom:16px;">📭</div>
    <div style="font-size:16px;font-weight:600;">No <?= $filter ?> proposals found</div>
</div>
<?php endif; ?>

<div class="row g-3">
<?php foreach ($proposals as $p): ?>
    <?php $data = json_decode($p['proposed_data'], true) ?: []; ?>
    <div class="col-12">
        <div class="card-kd" style="border-left:4px solid <?= ['pending'=>'var(--gold)','approved'=>'var(--success)','rejected'=>'var(--danger)'][$p['status']]??'var(--border)' ?>;">
            <div class="card-body-kd">
                <div style="display:flex;gap:16px;align-items:flex-start;flex-wrap:wrap;">
                    <!-- Photo preview -->
                    <?php $imgKey = $p['section']==='crop' ? 'image' : ($p['section']==='disease' ? 'image' : null); ?>
                    <?php if ($imgKey && !empty($data[$imgKey]) && file_exists(__DIR__.'/../'.$data[$imgKey])): ?>
                    <div style="flex-shrink:0;">
                        <img src="/KrishiDisha/<?= htmlspecialchars($data[$imgKey]) ?>" style="width:90px;height:70px;object-fit:cover;border-radius:10px;">
                    </div>
                    <?php endif; ?>

                    <!-- Info -->
                    <div style="flex:1;min-width:200px;">
                        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:6px;">
                            <?php $secIcons = ['crop'=>'🌱','disease'=>'🦠','recommender'=>'📍','nutrition'=>'🧪']; ?>
                            <span style="font-size:20px;"><?= $secIcons[$p['section']]??'📝' ?></span>
                            <div style="font-weight:700;font-size:15px;"><?= htmlspecialchars($p['title']) ?></div>
                            <span class="badge-kd badge-info" style="font-size:10px;text-transform:capitalize;"><?= ucfirst($p['section']) ?></span>
                            <?php
                                $sc = ['pending'=>'badge-warning','approved'=>'badge-success','rejected'=>'badge-danger'];
                                echo "<span class=\"badge-kd {$sc[$p['status']]}\" style=\"font-size:10px;\">".ucfirst($p['status'])."</span>";
                            ?>
                        </div>
                        <div style="font-size:12px;color:var(--text-muted);">
                            Submitted by <strong style="color:var(--text);"><?= htmlspecialchars($p['user_name']) ?></strong>
                            (<?= $p['user_email'] ?>)
                            · <?= date('d M Y, h:i a', strtotime($p['created_at'])) ?>
                        </div>

                        <!-- Data preview -->
                        <div style="background:var(--surface3);border-radius:8px;padding:10px 12px;margin-top:10px;font-size:12px;font-family:monospace;max-height:120px;overflow-y:auto;">
                            <?php foreach ($data as $key => $val): if ($key==='image') continue; ?>
                            <div><span style="color:var(--primary-dark);font-weight:600;"><?= htmlspecialchars($key) ?>:</span> <?= htmlspecialchars(mb_substr((string)$val,0,100)) ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div style="display:flex;flex-direction:column;gap:8px;flex-shrink:0;">
                        <?php if ($p['status'] === 'pending'): ?>
                        <a href="?approve=<?= $p['id'] ?>&filter=<?= $filter ?>" class="btn-kd btn-kd-primary" style="padding:6px 14px;font-size:12px;" data-confirm="Approve and add this to the platform?">
                            <i class="fa-solid fa-check"></i> Approve
                        </a>
                        <button onclick="showRejectModal(<?= $p['id'] ?>, '<?= $filter ?>')" class="btn-kd btn-kd-danger" style="padding:6px 14px;font-size:12px;">
                            <i class="fa-solid fa-times"></i> Reject
                        </button>
                        <?php elseif ($p['status'] === 'approved'): ?>
                        <div style="color:var(--success);font-size:12px;font-weight:700;"><i class="fa-solid fa-check-circle"></i> Approved</div>
                        <div style="font-size:11px;color:var(--text-muted);"><?= $p['reviewed_at'] ? date('d M Y',strtotime($p['reviewed_at'])) : '' ?></div>
                        <?php elseif ($p['status'] === 'rejected'): ?>
                        <div style="color:var(--danger);font-size:12px;font-weight:700;"><i class="fa-solid fa-times-circle"></i> Rejected</div>
                        <?php if ($p['admin_notes']): ?>
                        <div style="font-size:11px;color:var(--text-muted);max-width:120px;"><?= htmlspecialchars($p['admin_notes']) ?></div>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>

</div><!-- page-body -->
</div><!-- main-content -->
</div><!-- layout-wrapper -->

<!-- Reject Modal -->
<div id="rejectModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:var(--surface2);border-radius:16px;padding:28px;max-width:420px;width:90%;box-shadow:0 24px 48px rgba(0,0,0,0.3);">
        <h5 style="margin-bottom:16px;"><i class="fa-solid fa-times-circle me-2" style="color:var(--danger);"></i>Reject Proposal</h5>
        <div class="form-group">
            <label>Reason (optional)</label>
            <textarea id="rejectReason" class="form-control" rows="3" placeholder="Explain why this was rejected..."></textarea>
        </div>
        <div style="display:flex;gap:10px;margin-top:16px;">
            <button onclick="submitReject()" class="btn-kd btn-kd-danger" style="flex:1;justify-content:center;"><i class="fa-solid fa-times"></i> Reject</button>
            <button onclick="closeRejectModal()" class="btn-kd btn-kd-outline" style="flex:1;justify-content:center;">Cancel</button>
        </div>
    </div>
</div>

<script>
let rejectId = null, rejectFilter = '';
function showRejectModal(id, filter) { rejectId=id; rejectFilter=filter; document.getElementById('rejectModal').style.display='flex'; }
function closeRejectModal() { document.getElementById('rejectModal').style.display='none'; rejectId=null; }
function submitReject() {
    const reason = encodeURIComponent(document.getElementById('rejectReason').value);
    window.location = `proposals.php?reject=${rejectId}&filter=${rejectFilter}&reason=${reason}`;
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
