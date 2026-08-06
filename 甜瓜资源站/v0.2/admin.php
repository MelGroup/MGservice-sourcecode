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

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['admin']);
    header('Location: admin.php');
    exit;
}

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'login' && isset($_POST['password'])) {
        if (password_verify($_POST['password'], ADMIN_PASSWORD_HASH)) {
            $_SESSION['admin'] = true;
            header('Location: admin.php');
            exit;
        } else {
            $_SESSION['login_error'] = '密码错误';
        }
    }

    if (!isset($_SESSION['admin'])) {
        header('Location: admin.php');
        exit;
    }

    if ($_POST['action'] === 'delete' && isset($_POST['file'])) {
        $file = basename($_POST['file']);
        $filepath = $upload_dir . $file;
        $stmt = $db->prepare("DELETE FROM resources WHERE stored_name = ?");
        $stmt->execute([$file]);
        if (file_exists($filepath)) {
            unlink($filepath);
        }
        $_SESSION['admin_message'] = '删除成功';
        header('Location: admin.php' . (isset($_POST['tab']) ? '?tab=' . $_POST['tab'] : ''));
        exit;
    }

    if ($_POST['action'] === 'rename' && isset($_POST['file'], $_POST['new_name'])) {
        $file = basename($_POST['file']);
        $new_name = trim($_POST['new_name']);
        if ($new_name !== '') {
            $stmt = $db->prepare("SELECT original_name FROM resources WHERE stored_name = ?");
            $stmt->execute([$file]);
            $row = $stmt->fetch();
            $orig = $row ? $row['original_name'] : '';
            if (substr($orig, -4) === '.zip' && substr($new_name, -4) !== '.zip') {
                $new_name .= '.zip';
            }
            $stmt = $db->prepare("UPDATE resources SET original_name = ? WHERE stored_name = ?");
            $stmt->execute([$new_name, $file]);
            $_SESSION['admin_message'] = '重命名成功';
        }
        header('Location: admin.php' . (isset($_POST['tab']) ? '?tab=' . $_POST['tab'] : ''));
        exit;
    }

    if ($_POST['action'] === 'approve' && isset($_POST['file'])) {
        $file = basename($_POST['file']);
        $stmt = $db->prepare("UPDATE resources SET status = 'approved' WHERE stored_name = ?");
        $stmt->execute([$file]);
        $_SESSION['admin_message'] = '已通过';
        header('Location: admin.php?tab=pending');
        exit;
    }

    if ($_POST['action'] === 'reject' && isset($_POST['file'])) {
        $file = basename($_POST['file']);
        $filepath = $upload_dir . $file;
        $stmt = $db->prepare("DELETE FROM resources WHERE stored_name = ?");
        $stmt->execute([$file]);
        if (file_exists($filepath)) {
            unlink($filepath);
        }
        $_SESSION['admin_message'] = '已不通过';
        header('Location: admin.php?tab=pending');
        exit;
    }
}

$is_admin = isset($_SESSION['admin']) && $_SESSION['admin'] === true;
$tab = $_GET['tab'] ?? 'list';

function format_size($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}

if (!$is_admin):
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>管理后台 - 甜瓜资源站</title>
<style>
::selection{background:#9c88ff;color:#1a1a2e}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#f0f2f5;color:#1a1a2e;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.toast{position:fixed;top:20px;left:50%;transform:translateX(-50%);z-index:3000;padding:12px 24px;font-weight:600;font-size:0.9rem;border:2px solid #1a1a2e;box-shadow:2px 2px 0px 0px rgba(0,0,0,0.9);transition:opacity 0.3s ease;max-width:90vw;background:#fff}
.toast.err{background:#ffebee;color:#c62828;border-color:#c62828}
.login-card{background:#fff;border:2px solid #1a1a2e;padding:40px;max-width:420px;width:100%;box-shadow:4px 4px 0px 0px #5a4bd6}
.login-card h2{font-size:1.4rem;margin-bottom:24px;text-align:center}
.form-group{margin-bottom:18px}
.form-group label{display:block;margin-bottom:6px;font-weight:700;font-size:0.9rem}
.form-group input[type="password"]{width:100%;padding:10px 12px;border:2px solid #ddd;background:#fafafa;font-size:15px;font-family:inherit;transition:border-color 0.2s}
.form-group input:focus{border-color:#5a4bd6;outline:none;background:#fff}
.btn-hard{display:inline-block;border:2px solid #1a1a2e;padding:0.5rem 1.25rem;font-weight:700;font-size:0.9rem;text-decoration:none;cursor:pointer;box-shadow:2px 2px 0px 0px rgba(0,0,0,0.9);transition:all 0.15s;font-family:inherit;background:#fff;color:#1a1a2e;width:100%;padding:0.75rem;font-size:1rem;text-align:center}
.btn-hard:active{transform:translate(2px,2px);box-shadow:none}
.btn-hard.primary{background:#5a4bd6;color:#fff}
.btn-hard.primary:hover{background:#6c5ce7}
@media(max-width:480px){
.login-card{padding:24px}
}
</style>
</head>
<body>
<div class="login-card">
<h2>管理员登录</h2>
<script>
function showToast(msg,t){var d=document.createElement('div');d.className='toast '+t;d.textContent=msg;document.body.appendChild(d);setTimeout(function(){d.style.opacity='0';setTimeout(function(){d.remove()},300)},3000)}
</script>
<?php if (isset($_SESSION['login_error'])): ?>
<script>showToast('<?= addslashes($_SESSION['login_error']) ?>','err')</script>
<?php unset($_SESSION['login_error']); endif; ?>
<form method="post">
<input type="hidden" name="action" value="login">
<div class="form-group">
<label for="password">管理员密码</label>
<input type="password" name="password" id="password" required>
</div>
<button type="submit" class="btn-hard primary">登录</button>
</form>
</div>
</body>
</html>
<?php else:
$active_sidebar = ($tab === 'pending') ? 'pending' : 'list';
render_header('管理后台', '
.main-admin{margin-left:180px;padding:30px 24px 40px;max-width:1100px}
.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:28px;padding-bottom:14px;border-bottom:3px solid #5a4bd6}
.page-header h1{font-size:1.8rem;color:#1a1a2e}
h2{margin-bottom:16px;font-size:1.3rem}
.table-wrapper{background:#fff;border:2px solid #1a1a2e;overflow-x:auto;box-shadow:4px 4px 0px 0px #5a4bd6}
.res-table{width:100%;border-collapse:collapse;min-width:650px}
.res-table th{text-align:left;padding:14px 12px;background:#f5f5f5;font-weight:700;color:#1a1a2e;border-bottom:2px solid #1a1a2e;font-size:0.85rem}
.res-table td{padding:12px;border-bottom:1px solid #eee;font-size:0.9rem}
.res-table tr:last-child td{border-bottom:none}
.res-table tr:hover td{background:#f8f6ff}
.file-name-col{min-width:180px;max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.ops{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
.ops .btn-hard{font-size:0.8rem;padding:0.35rem 0.75rem}
.empty{text-align:center;padding:40px;color:#888}
.tag{display:inline-block;padding:2px 8px;font-size:0.75rem;font-weight:700;border:2px solid #1a1a2e;margin-left:6px}
.tag.pending{background:#fff3cd;color:#856404;border-color:#856404}
.tag.approved{background:#d4edda;color:#155724;border-color:#155724}
.modal{display:none;position:fixed;top:0;left:0;right:0;bottom:0;z-index:2000;align-items:center;justify-content:center}
.modal.show{display:flex}
.modal-bg{position:absolute;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5)}
.modal-box{position:relative;background:#fff;border:2px solid #1a1a2e;padding:24px;width:360px;max-width:90vw;box-shadow:4px 4px 0px 0px #5a4bd6}
.modal-box h3{font-size:1.1rem;margin-bottom:16px}
.modal-box input[type="text"]{width:100%;padding:10px 12px;border:2px solid #ddd;font-size:0.95rem;font-family:inherit;margin-bottom:16px}
.modal-box input[type="text"]:focus{border-color:#5a4bd6;outline:none}
.modal-btns{display:flex;gap:8px;justify-content:flex-end}
@media(max-width:768px){
.main-admin{margin-left:0;padding:20px 16px 30px}
.res-table{font-size:0.85rem}
.file-name-col{min-width:120px;max-width:180px}
}
@media(max-width:480px){
.main-admin{padding:16px 12px 24px}
.page-header h1{font-size:1.4rem}
}
');
render_admin_sidebar($active_sidebar);
?>
<div class="main-admin">
<div class="page-header">
<h1><?= $tab === 'pending' ? '待审核' : '资源列表' ?></h1>
<a href="?action=logout" class="btn-hard danger"><i class="fas fa-sign-out-alt"></i> 退出登录</a>
</div>
<?php if (isset($_SESSION['admin_message'])): ?>
<script>showToast('<?= addslashes($_SESSION['admin_message']) ?>','ok')</script>
<?php unset($_SESSION['admin_message']); endif; ?>
<div class="table-wrapper">
<?php
if ($tab === 'pending') {
    $resources = $db->query("SELECT * FROM resources WHERE status = 'pending' ORDER BY upload_time DESC")->fetchAll();
    if (empty($resources)): ?>
<div class="empty">暂无待审核资源</div>
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
<?php $fp = $upload_dir . $res['stored_name']; ?>
<tr>
<td class="file-name-col" title="<?= htmlspecialchars($res['original_name']) ?>"><?= htmlspecialchars(substr($res['original_name'], -4) === '.zip' ? substr($res['original_name'], 0, -4) : $res['original_name']) ?></td>
<td><?= file_exists($fp) ? format_size(filesize($fp)) : '?' ?></td>
<td><?= date('Y-m-d H:i', $res['upload_time']) ?></td>
<td class="ops">
<a href="?download=1&file=<?= urlencode($res['stored_name']) ?>" class="btn-hard" style="background:#3498db;color:#fff;border-color:#3498db;width:36px;height:36px;padding:0;display:inline-flex;align-items:center;justify-content:center"><i class="fas fa-download"></i></a>
<form method="post" style="display:inline">
<input type="hidden" name="action" value="approve">
<input type="hidden" name="file" value="<?= htmlspecialchars($res['stored_name']) ?>">
<button type="submit" class="btn-hard" style="background:#27ae60;color:#fff;border-color:#27ae60;width:36px;height:36px;padding:0;display:inline-flex;align-items:center;justify-content:center"><i class="fas fa-check"></i></button>
</form>
<form method="post" style="display:inline" onsubmit="return confirm('确认不通过？将同时删除文件')">
<input type="hidden" name="action" value="reject">
<input type="hidden" name="file" value="<?= htmlspecialchars($res['stored_name']) ?>">
<button type="submit" class="btn-hard" style="background:#e74c3c;color:#fff;border-color:#e74c3c;width:36px;height:36px;padding:0;display:inline-flex;align-items:center;justify-content:center"><i class="fas fa-times"></i></button>
</form>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif;
} else {
    $resources = $db->query("SELECT * FROM resources WHERE status = 'approved' ORDER BY upload_time DESC")->fetchAll();
    if (empty($resources)): ?>
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
<?php $fp = $upload_dir . $res['stored_name']; ?>
<tr>
<td class="file-name-col" title="<?= htmlspecialchars($res['original_name']) ?>"><?= htmlspecialchars(substr($res['original_name'], -4) === '.zip' ? substr($res['original_name'], 0, -4) : $res['original_name']) ?></td>
<td><?= file_exists($fp) ? format_size(filesize($fp)) : '?' ?></td>
<td><?= date('Y-m-d H:i', $res['upload_time']) ?></td>
<td class="ops">
<a href="?download=1&file=<?= urlencode($res['stored_name']) ?>" class="btn-hard" style="background:#3498db;color:#fff;border-color:#3498db;width:36px;height:36px;padding:0;display:inline-flex;align-items:center;justify-content:center"><i class="fas fa-download"></i></a>
<button class="btn-hard" onclick="openRename('<?= htmlspecialchars($res['stored_name']) ?>','<?= htmlspecialchars(substr($res['original_name'], -4) === '.zip' ? substr($res['original_name'], 0, -4) : $res['original_name']) ?>')" style="background:#f39c12;color:#fff;border-color:#f39c12;width:36px;height:36px;padding:0;display:inline-flex;align-items:center;justify-content:center"><i class="fas fa-pen"></i></button>
<form method="post" style="display:inline" onsubmit="return confirm('确认删除？')">
<input type="hidden" name="action" value="delete">
<input type="hidden" name="file" value="<?= htmlspecialchars($res['stored_name']) ?>">
<input type="hidden" name="tab" value="list">
<button type="submit" class="btn-hard" style="background:#e74c3c;color:#fff;border-color:#e74c3c;width:36px;height:36px;padding:0;display:inline-flex;align-items:center;justify-content:center"><i class="fas fa-trash-alt"></i></button>
</form>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif;
}
?>
</div>
</div>
<div class="modal" id="renameModal">
<div class="modal-bg" onclick="closeRename()"></div>
<div class="modal-box">
<h3>重命名</h3>
<form method="post" id="renameForm">
<input type="hidden" name="action" value="rename">
<input type="hidden" name="file" id="renameFile" value="">
<input type="hidden" name="tab" value="list">
<input type="text" name="new_name" id="renameInput" required>
<div class="modal-btns">
<button type="button" class="btn-hard" onclick="closeRename()" style="padding:0.4rem 1rem">取消</button>
<button type="submit" class="btn-hard primary" style="padding:0.4rem 1rem">确认</button>
</div>
</form>
</div>
</div>
<script>
function openRename(file, name) {
document.getElementById('renameFile').value = file;
document.getElementById('renameInput').value = name;
document.getElementById('renameModal').classList.add('show');
}
function closeRename() {
document.getElementById('renameModal').classList.remove('show');
}
</script>
<?php render_footer(); ?>
<?php endif; ?>
