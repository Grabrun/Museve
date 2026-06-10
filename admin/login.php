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
    <link rel="icon" href="/resources/images/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif+SC:wght@400;500;600;700&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    'museve-bg': '#F9F7F4',
                    'museve-rose': '#DDB8B8',
                    'museve-rose-deep': '#B28B8B',
                    'museve-night': '#3E3640',
                    'museve-gray': '#8E827F',
                },
                fontFamily: {
                    'serif': ['Noto Serif SC', 'serif'],
                    'sans': ['Inter', 'system-ui', 'sans-serif'],
                }
            }
        }
    }
    </script>
    <style>
        .login-card { animation: fadeUp 0.5s ease-out; }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-museve-bg text-museve-night min-h-screen flex items-center justify-center px-4 font-sans">

<div class="login-card w-full max-w-sm">
    <!-- Brand -->
    <div class="text-center mb-8">
        <img src="/resources/images/logo.svg" alt="Logo" class="h-12 w-12 mx-auto mb-3">
        <h1 class="text-2xl font-serif font-semibold tracking-wide"><?= htmlspecialchars($siteTitle) ?></h1>
        <p class="text-museve-gray text-xs mt-1 font-serif"><?= htmlspecialchars($siteSubtitle) ?></p>
    </div>

    <!-- Login Card -->
    <div class="bg-white/70 backdrop-blur-xl rounded-2xl shadow-[0_4px_20px_rgba(62,54,64,0.06)] border border-white/60 p-8">
        <h2 class="text-base font-semibold text-center mb-6 text-museve-night">后花园 · 管理后台</h2>

        <div id="login-error" class="hidden bg-[#D18B8B]/10 text-[#D18B8B] text-sm rounded-lg px-4 py-2.5 mb-4"></div>

        <form id="login-form" class="space-y-4">
            <div>
                <label for="account" class="block text-xs font-medium text-museve-gray mb-1.5">账号</label>
                <input type="text" id="account" name="account" required autocomplete="username"
                       class="w-full px-4 py-2.5 rounded-lg border border-[#E5E0DB] bg-[#F9F7F4] text-sm focus:outline-none focus:border-museve-rose focus:ring-1 focus:ring-museve-rose/30 transition-colors"
                       placeholder="请输入账号">
            </div>

            <div>
                <label for="password" class="block text-xs font-medium text-museve-gray mb-1.5">密码</label>
                <input type="password" id="password" name="password" required autocomplete="current-password"
                       class="w-full px-4 py-2.5 rounded-lg border border-[#E5E0DB] bg-[#F9F7F4] text-sm focus:outline-none focus:border-museve-rose focus:ring-1 focus:ring-museve-rose/30 transition-colors"
                       placeholder="请输入密码">
            </div>

            <button type="submit" id="login-btn"
                    class="w-full py-2.5 bg-museve-rose text-white text-sm font-medium rounded-lg hover:bg-museve-rose-deep active:bg-[#A07878] transition-colors focus:outline-none focus:ring-2 focus:ring-museve-rose focus:ring-offset-2 disabled:opacity-50">
                登 录
            </button>
        </form>
    </div>

    <p class="text-center text-xs text-museve-gray/50 mt-6">&copy; <?= date('Y') ?> <?= htmlspecialchars($siteTitle) ?></p>
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
        if (data.code === 200) {
            window.location.href = '/admin';
        } else {
            errDiv.textContent = data.message || '登录失败';
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
