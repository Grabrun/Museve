<?php
// 暮想 Museve 后台登录页
$db = getDB();
$stmt = $db->query("SELECT `key`, `value` FROM `settings` WHERE `key` IN ('site_title', 'site_subtitle')");
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['key']] = $row['value'];
}
$siteTitle = $settings['site_title'] ?? '暮想';
$siteSubtitle = $settings['site_subtitle'] ?? '在薄暮时分，温柔地想起。';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登录 - <?= htmlspecialchars($siteTitle) ?> 管理后台</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    'dusk-white': '#F9F7F4',
                    'dusk-rose': '#C4A6B8',
                    'dusk-text': '#3E3640',
                    'dusk-muted': '#B8A9B0',
                }
            }
        }
    }
    </script>
    <style>
        body { font-family: 'Noto Serif SC', serif; }
        .login-card {
            animation: fadeUp 0.5s ease-out;
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-dusk-white text-dusk-text min-h-screen flex items-center justify-center px-4">

<div class="login-card w-full max-w-sm">
    <!-- Brand -->
    <div class="text-center mb-8">
        <h1 class="text-3xl font-bold tracking-wide"><?= htmlspecialchars($siteTitle) ?></h1>
        <p class="text-dusk-muted text-sm mt-2"><?= htmlspecialchars($siteSubtitle) ?></p>
    </div>

    <!-- Login Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-[#EDE8E4] p-8">
        <h2 class="text-lg font-semibold text-center mb-6">管理后台</h2>

        <div id="login-error" class="hidden bg-red-50 text-red-600 text-sm rounded-lg px-4 py-2 mb-4"></div>

        <form id="login-form" class="space-y-4">
            <div>
                <label for="account" class="block text-sm font-medium mb-1.5">账号</label>
                <input type="text" id="account" name="account" required
                       class="w-full px-4 py-2.5 rounded-lg border border-[#EDE8E4] bg-[#FAFAF8] focus:outline-none focus:border-dusk-rose focus:ring-1 focus:ring-dusk-rose transition-colors"
                       placeholder="请输入账号" autocomplete="username">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium mb-1.5">密码</label>
                <input type="password" id="password" name="password" required
                       class="w-full px-4 py-2.5 rounded-lg border border-[#EDE8E4] bg-[#FAFAF8] focus:outline-none focus:border-dusk-rose focus:ring-1 focus:ring-dusk-rose transition-colors"
                       placeholder="请输入密码" autocomplete="current-password">
            </div>

            <button type="submit" id="login-btn"
                    class="w-full py-2.5 bg-dusk-rose text-white font-medium rounded-lg hover:bg-[#B898A8] active:bg-[#A88898] transition-colors focus:outline-none focus:ring-2 focus:ring-dusk-rose focus:ring-offset-2">
                登 录
            </button>
        </form>
    </div>

    <!-- Footer -->
    <p class="text-center text-xs text-dusk-muted mt-6">
        &copy; <?= date('Y') ?> <?= htmlspecialchars($siteTitle) ?>
    </p>
</div>

<script>
document.getElementById('login-form').addEventListener('submit', function(e) {
    e.preventDefault();
    var btn = document.getElementById('login-btn');
    var errDiv = document.getElementById('login-error');
    var account = document.getElementById('account').value.trim();
    var password = document.getElementById('password').value;

    if (!account || !password) {
        errDiv.textContent = '请填写账号和密码';
        errDiv.classList.remove('hidden');
        return;
    }

    btn.disabled = true;
    btn.textContent = '登录中…';
    errDiv.classList.add('hidden');

    fetch('/admin/api/auth', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ account: account, password: password })
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            window.location.href = data.redirect || '/admin';
        } else {
            errDiv.textContent = data.error || '登录失败';
            errDiv.classList.remove('hidden');
            btn.disabled = false;
            btn.textContent = '登 录';
        }
    })
    .catch(function() {
        errDiv.textContent = '网络错误，请稍后重试';
        errDiv.classList.remove('hidden');
        btn.disabled = false;
        btn.textContent = '登 录';
    });
});
</script>

</body>
</html>
