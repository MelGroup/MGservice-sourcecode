<?php
function render_header($title, $extra_css = '') {
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $title ?> - 甜瓜资源站</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
::selection{background:#9c88ff;color:#1a1a2e}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#f0f2f5;color:#1a1a2e;min-height:100vh}
.sidebar{position:fixed;top:0;left:0;bottom:0;width:180px;background:#fff;border-right:2px solid #5a4bd6;z-index:1000;padding:20px 12px;display:flex;flex-direction:column;transform:translateX(-100%);transition:transform 0.3s ease}
.sidebar.open{transform:translateX(0)}
.sidebar-brand{font-size:1.3rem;font-weight:700;text-align:center;color:#5a4bd6;margin-bottom:24px;padding-bottom:12px;border-bottom:2px solid #eee}
.nav-item{display:flex;align-items:center;gap:8px;padding:10px 12px;margin-bottom:4px;font-size:0.9rem;color:#1a1a2e;text-decoration:none;border:2px solid transparent;transition:all 0.15s}
.nav-item i{width:18px;text-align:center;font-size:0.9rem}
.nav-item:hover{background:#f0eeff;border-color:#5a4bd6;box-shadow:2px 2px 0px 0px rgba(90,75,214,0.85)}
.nav-item.active{background:#5a4bd6;color:#fff;border-color:#5a4bd6;box-shadow:2px 2px 0px 0px rgba(90,75,214,0.85)}
.nav-item:active{transform:translate(1px,1px);box-shadow:none}
.main{margin-left:0;padding:30px 24px 40px;max-width:1100px}
.page-header{margin-bottom:28px;padding-bottom:14px;border-bottom:3px solid #5a4bd6}
.page-header h1{font-size:1.8rem;color:#1a1a2e}
.page-header .sub{font-size:0.85rem;color:#888;margin-top:4px}
.btn-hard{display:inline-block;border:2px solid #1a1a2e;padding:0.5rem 1.25rem;font-weight:700;font-size:0.9rem;text-decoration:none;cursor:pointer;box-shadow:2px 2px 0px 0px rgba(0,0,0,0.9);transition:all 0.15s;font-family:inherit;background:#fff;color:#1a1a2e}
.btn-hard:active{transform:translate(2px,2px);box-shadow:none}
.btn-hard.primary{background:#5a4bd6;color:#fff}
.btn-hard.primary:hover{background:#6c5ce7}
.btn-hard.danger{background:#e74c3c;color:#fff}
.btn-hard.danger:hover{background:#c0392b}
.mobile-header{display:flex;align-items:center;justify-content:space-between;background:#fff;padding:12px 16px;border-bottom:2px solid #5a4bd6;position:sticky;top:0;z-index:999}
.mobile-header .brand{font-size:1.2rem;font-weight:700;color:#5a4bd6}
.menu-toggle{font-size:1.3rem;cursor:pointer;color:#1a1a2e;background:none;border:none;padding:4px}
.overlay{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.4);z-index:999;opacity:0;pointer-events:none;transition:opacity 0.3s ease}
.overlay.show{opacity:1;pointer-events:auto}
.toast{position:fixed;top:20px;left:50%;transform:translateX(-50%);z-index:3000;padding:12px 24px;font-weight:600;font-size:0.9rem;border:2px solid #1a1a2e;box-shadow:2px 2px 0px 0px rgba(0,0,0,0.9);transition:opacity 0.3s ease;max-width:90vw;background:#fff}
.toast.ok{background:#e8f5e9;color:#2e7d32;border-color:#2e7d32}
.toast.err{background:#ffebee;color:#c62828;border-color:#c62828}
<?= $extra_css ?>
</style>
<script>
function showToast(msg,t){var d=document.createElement('div');d.className='toast '+t;d.textContent=msg;document.body.appendChild(d);setTimeout(function(){d.style.opacity='0';setTimeout(function(){d.remove()},300)},3000)}
</script>
</head>
<body>
<?php
}

function render_sidebar($active = '') {
?>
<div class="mobile-header">
<div class="brand">甜瓜资源站</div>
<i class="fas fa-bars menu-toggle" onclick="toggleSidebar()"></i>
</div>
<div class="sidebar" id="sidebar">
<div class="sidebar-brand">甜瓜资源站</div>
<a href="index.php" class="nav-item<?= $active === 'list' ? ' active' : '' ?>"><i class="fas fa-list"></i>资源列表</a>
<a href="upload.php" class="nav-item<?= $active === 'upload' ? ' active' : '' ?>"><i class="fas fa-cloud-upload-alt"></i>上传资源</a>
<?php if ($active === 'admin'): ?>
<a href="admin.php" class="nav-item active"><i class="fas fa-cog"></i>管理后台</a>
<?php endif; ?>
</div>
<div class="overlay" id="overlay" onclick="toggleSidebar()"></div>
<?php
}

function render_admin_sidebar($active = 'list') {
?>
<div class="mobile-header">
<div class="brand">管理后台</div>
<i class="fas fa-bars menu-toggle" onclick="toggleSidebar()"></i>
</div>
<div class="sidebar" id="sidebar">
<div class="sidebar-brand">管理后台</div>
<a href="admin.php" class="nav-item<?= $active === 'list' ? ' active' : '' ?>"><i class="fas fa-list"></i>资源列表</a>
<a href="admin.php?tab=pending" class="nav-item<?= $active === 'pending' ? ' active' : '' ?>"><i class="fas fa-clock"></i>待审核</a>
</div>
<div class="overlay" id="overlay" onclick="toggleSidebar()"></div>
<?php
}

function render_footer() {
?>
<script>
function toggleSidebar() {
document.getElementById('sidebar').classList.toggle('open');
document.getElementById('overlay').classList.toggle('show');
}
</script>
</body>
</html>
<?php
}
?>