<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/layout.php';

$upload_dir = __DIR__ . '/uploads/';
$max_size = 5 * 1024 * 1024;
$allowed_ext = ['zip'];

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['resource_file'])) {
    $file = $_FILES['resource_file'];
    $filename = $file['name'];
    $tmp_name = $file['tmp_name'];
    $size = $file['size'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed_ext)) {
        $_SESSION['error'] = '只允许上传 ZIP 文件';
    } elseif ($size > $max_size) {
        $_SESSION['error'] = '文件大小不能超过 5MB';
    } elseif (!is_uploaded_file($tmp_name)) {
        $_SESSION['error'] = '上传失败，请重试';
    } else {
        $stored_name = uniqid() . '.zip';
        $dest = $upload_dir . $stored_name;

        if (move_uploaded_file($tmp_name, $dest)) {
            $db = getDB();
            $stmt = $db->prepare("INSERT INTO resources (original_name, stored_name, upload_time, status) VALUES (?, ?, ?, ?)");
            $stmt->execute([$filename, $stored_name, time(), 'pending']);
            $_SESSION['message'] = '上传成功';
        } else {
            $_SESSION['error'] = '文件保存失败';
        }
    }
    header('Location: upload.php');
    exit;
}

render_header('上传资源', '
.upload-card{background:#fff;border:2px solid #1a1a2e;padding:24px;max-width:500px;box-shadow:4px 4px 0px 0px #5a4bd6}
.upload-card h2{font-size:1.2rem;margin-bottom:16px}
.form-file{display:flex;flex-direction:column;gap:12px}
.form-file input[type="file"]{padding:10px 12px;border:2px solid #ddd;background:#fafafa;font-size:0.9rem;font-family:inherit;width:100%;transition:border-color 0.2s}
.form-file input[type="file"]:focus{border-color:#5a4bd6;outline:none}
.upload-card .btn-hard{width:100%;padding:0.75rem;font-size:1rem;text-align:center}
@media(max-width:480px){
.main{padding:16px 12px 24px}
.page-header h1{font-size:1.4rem}
.upload-card{padding:16px;max-width:100%}
}
');
render_sidebar('upload');
?>
<div class="main">
<div class="page-header">
<h1>上传资源</h1>
<div class="sub">上传 ZIP 文件，最大 5MB</div>
</div>
<?php if (isset($_SESSION['message'])): ?>
<script>showToast('<?= addslashes($_SESSION['message']) ?>','ok')</script>
<?php unset($_SESSION['message']); endif; ?>
<?php if (isset($_SESSION['error'])): ?>
<script>showToast('<?= addslashes($_SESSION['error']) ?>','err')</script>
<?php unset($_SESSION['error']); endif; ?>
<div class="upload-card">
<h2><i class="fas fa-upload"></i> 选择文件</h2>
<form method="post" enctype="multipart/form-data" class="form-file">
<input type="file" name="resource_file" accept=".zip" required>
<button type="submit" class="btn-hard primary"><i class="fas fa-cloud-upload-alt"></i> 上传 (最大 5MB)</button>
</form>
</div>
</div>
<?php render_footer(); ?>