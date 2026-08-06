<?php
session_start();

if (file_exists(__DIR__ . '/config.php')) {
    die('网站已安装如需重新安装，请删除 config.php 后再访问本页面');
}

$step = $_GET['step'] ?? 1;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = $_POST['db_host'] ?? 'localhost';
    $db_user = $_POST['db_user'] ?? '';
    $db_pass = $_POST['db_pass'] ?? '';
    $db_name = $_POST['db_name'] ?? '';
    $admin_password = $_POST['admin_password'] ?? '';

    if (empty($db_user) || empty($db_name) || empty($admin_password)) {
        $error = '请填写所有必填项';
    } else {
        try {
            $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
            $pdo = new PDO($dsn, $db_user, $db_pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            $pdo->exec("CREATE TABLE IF NOT EXISTS resources (
                id INT AUTO_INCREMENT PRIMARY KEY,
                original_name VARCHAR(255) NOT NULL,
                stored_name VARCHAR(255) NOT NULL,
                upload_time INT NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'pending'
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $hash = password_hash($admin_password, PASSWORD_DEFAULT);

            $config_content = "<?php\n\n";
            $config_content .= "define('DB_HOST', " . var_export($db_host, true) . ");\n";
            $config_content .= "define('DB_USER', " . var_export($db_user, true) . ");\n";
            $config_content .= "define('DB_PASS', " . var_export($db_pass, true) . ");\n";
            $config_content .= "define('DB_NAME', " . var_export($db_name, true) . ");\n";
            $config_content .= "define('ADMIN_PASSWORD_HASH', " . var_export($hash, true) . ");\n";

            file_put_contents(__DIR__ . '/config.php', $config_content);

            if (!file_exists(__DIR__ . '/uploads')) {
                mkdir(__DIR__ . '/uploads', 0755, true);
            }

            $_SESSION['install_success'] = true;
            header('Location: install.php?step=3');
            exit;
        } catch (PDOException $e) {
            $error = '数据库连接失败：' . $e->getMessage();
        }
    }
}

if (($step == 3) && isset($_SESSION['install_success'])) {
    unset($_SESSION['install_success']);
    ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>安装成功 - 甜瓜资源站</title>
<style>
::selection{background:#9c88ff;color:#1a1a2e}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#f0f2f5;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.box{background:#fff;border:2px solid #1a1a2e;padding:40px;max-width:420px;width:100%;text-align:center;box-shadow:4px 4px 0px 0px #5a4bd6}
h2{color:#2e7d32;font-size:1.5rem;margin-bottom:12px}
p{color:#555;margin-bottom:8px;font-size:0.95rem;line-height:1.6}
.warning{color:#d32f2f;font-weight:700;margin:20px 0 8px;font-size:0.9rem}
code{background:#f5f5f5;padding:2px 6px;border:1px solid #ddd;border-radius:3px;font-size:0.85rem}
.btns{display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-top:24px}
.btn-hard{display:inline-block;border:2px solid #1a1a2e;padding:0.625rem 1.5rem;font-weight:700;text-decoration:none;color:#fff;background:#4CAF50;box-shadow:2px 2px 0px 0px rgba(0,0,0,0.9);transition:all 0.15s;cursor:pointer;font-size:0.95rem}
.btn-hard:active{transform:translate(2px,2px);box-shadow:none}
.btn-hard.blue{background:#5a4bd6}
</style>
</head>
<body>
<div class="box">
<h2>安装成功！</h2>
<p>数据库已配置，管理员账户已创建</p>
<p class="warning">请立即删除服务器上的 <code>install.php</code> 文件，以免被他人利用</p>
<div class="btns">
<a href="index.php" class="btn-hard">进入网站</a>
<a href="admin.php" class="btn-hard blue">管理后台</a>
</div>
</div>
</body>
</html>
<?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>安装向导 - 甜瓜资源站</title>
<style>
::selection{background:#9c88ff;color:#1a1a2e}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#f0f2f5;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.install-box{background:#fff;border:2px solid #1a1a2e;padding:35px 40px;max-width:480px;width:100%;box-shadow:4px 4px 0px 0px #5a4bd6}
h2{font-size:1.5rem;margin-bottom:24px;text-align:center;color:#1a1a2e}
.form-group{margin-bottom:18px}
label{display:block;margin-bottom:6px;font-weight:700;color:#1a1a2e;font-size:0.9rem}
input[type="text"],input[type="password"]{width:100%;padding:10px 12px;border:2px solid #ddd;border-radius:0;font-size:15px;transition:border-color 0.2s;background:#fafafa;font-family:inherit}
input:focus{border-color:#5a4bd6;outline:none;background:#fff}
.error{background:#ffebee;color:#c62828;padding:10px 14px;margin-bottom:18px;font-size:14px;border:2px solid #c62828;box-shadow:2px 2px 0px 0px rgba(0,0,0,0.9)}
.btn-hard{width:100%;padding:0.75rem;border:2px solid #1a1a2e;font-weight:700;font-size:1rem;cursor:pointer;background:#5a4bd6;color:#fff;box-shadow:2px 2px 0px 0px rgba(0,0,0,0.9);transition:all 0.15s;font-family:inherit}
.btn-hard:hover{background:#6c5ce7}
.btn-hard:active{transform:translate(2px,2px);box-shadow:none}
.hint{font-size:13px;color:#777;margin-top:4px}
@media(max-width:480px){.install-box{padding:24px 20px}}
</style>
</head>
<body>
<div class="install-box">
<h2>甜瓜资源站 · 安装</h2>
<?php if ($error): ?>
<div class="error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<form method="post">
<div class="form-group">
<label>数据库主机</label>
<input type="text" name="db_host" value="localhost" required>
</div>
<div class="form-group">
<label>数据库用户名</label>
<input type="text" name="db_user" required>
</div>
<div class="form-group">
<label>数据库密码</label>
<input type="password" name="db_pass">
</div>
<div class="form-group">
<label>数据库名称</label>
<input type="text" name="db_name" required>
<div class="hint">请确保数据库已存在，程序将自动创建所需数据表</div>
</div>
<div class="form-group">
<label>管理员密码</label>
<input type="password" name="admin_password" required>
</div>
<button type="submit" class="btn-hard">开始安装</button>
</form>
</div>
</body>
</html>
