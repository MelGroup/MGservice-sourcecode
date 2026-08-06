<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/layout.php';

$upload_dir = __DIR__ . '/uploads/';

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    try {
        $pdo->exec("ALTER TABLE resources ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'pending'");
        $pdo->exec("UPDATE resources SET status = 'approved' WHERE status IS NULL OR status = ''");
    } catch (PDOException $e) {
    }
    return $pdo;
}

if (isset($_GET['download'], $_GET['file'])) {
    $file_basename = basename($_GET['file']);
    $filepath = $upload_dir . $file_basename;
    if (!file_exists($filepath)) {
        die('文件不存在');
    }
    $db = getDB();
    $stmt = $db->prepare("SELECT original_name FROM resources WHERE stored_name = ? LIMIT 1");
    $stmt->execute([$file_basename]);
    $row = $stmt->fetch();
    $download_name = $row ? $row['original_name'] : $file_basename;
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . addslashes($download_name) . '"');
    header('Content-Length: ' . filesize($filepath));
    readfile($filepath);
    exit;
}

$db = getDB();
$resources = $db->query("SELECT * FROM resources WHERE status = 'approved' ORDER BY upload_time DESC")->fetchAll();

function format_size($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}

render_header('资源列表', '
h2{margin-bottom:16px;font-size:1.3rem}
.table-wrapper{background:#fff;border:2px solid #1a1a2e;overflow-x:auto;box-shadow:4px 4px 0px 0px #5a4bd6}
.res-table{width:100%;border-collapse:collapse;min-width:550px}
.res-table th{text-align:left;padding:14px 12px;background:#f5f5f5;font-weight:700;color:#1a1a2e;border-bottom:2px solid #1a1a2e;font-size:0.85rem}
.res-table td{padding:12px;border-bottom:1px solid #eee;font-size:0.9rem}
.res-table tr:last-child td{border-bottom:none}
.res-table tr:hover td{background:#f8f6ff}
.file-name-col{min-width:200px;max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.download-link{color:#5a4bd6;text-decoration:none;font-weight:700;font-size:0.85rem}
.download-link:hover{text-decoration:underline}
.empty{text-align:center;padding:40px;color:#888}
@media(max-width:768px){
.res-table{font-size:0.85rem}
.file-name-col{min-width:140px;max-width:200px}
}
@media(max-width:480px){
.main{padding:16px 12px 24px}
.page-header h1{font-size:1.4rem}
}
');
render_sidebar('list');
?>
<div class="main">
<div class="page-header">
<h1>资源列表</h1>
<div class="sub">举报资源请联系:</div>
</div>
<?php if (isset($_SESSION['message'])): ?>
<script>showToast('<?= addslashes($_SESSION['message']) ?>','ok')</script>
<?php unset($_SESSION['message']); endif; ?>
<?php if (isset($_SESSION['error'])): ?>
<script>showToast('<?= addslashes($_SESSION['error']) ?>','err')</script>
<?php unset($_SESSION['error']); endif; ?>
<div class="table-wrapper">
<?php if (empty($resources)): ?>
<div class="empty">暂无资源</div>
<?php else: ?>
<table class="res-table">
<thead>
<tr>
<th class="file-name-col">文件名</th>
<th>大小</th>
<th>上传时间</th>
<th>操作</th>
</tr>
</thead>
<tbody>
<?php foreach ($resources as $res): ?>
<tr>
<td class="file-name-col" title="<?= htmlspecialchars($res['original_name']) ?>"><?= htmlspecialchars(substr($res['original_name'], -4) === '.zip' ? substr($res['original_name'], 0, -4) : $res['original_name']) ?></td>
<td><?= format_size(filesize($upload_dir . $res['stored_name'])) ?></td>
<td><?= date('Y-m-d H:i', $res['upload_time']) ?></td>
<td><a href="?download=1&file=<?= urlencode($res['stored_name']) ?>" class="btn-hard" style="background:#27ae60;color:#fff;border-color:#27ae60;width:36px;height:36px;padding:0;display:inline-flex;align-items:center;justify-content:center"><i class="fas fa-download"></i></a></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
</div>
</div>
<?php render_footer(); ?>
