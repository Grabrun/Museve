/**
 * 暮想 Museve — 后台管理脚本
 * Toast · 模态框 · 删除确认 · 工具函数
 */

/* ==========================================
 * 0. CSRF Token
 * ========================================== */
function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : '';
}

function csrfHeaders(headers = {}) {
    return { ...headers, 'X-CSRF-Token': getCsrfToken() };
}

/* ==========================================
 * 1. Toast 通知
 * ========================================== */
function showToast(message, type = 'success') {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;
    container.appendChild(toast);

    requestAnimationFrame(() => {
        toast.classList.add('show');
    });

    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

/* ==========================================
 * 2. 模态框
 * ========================================== */
function openModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
}

function closeModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
}

/* ==========================================
 * 3. 删除确认
 * ========================================== */
async function deleteItem(type, id) {
    if (!confirm('确定要删除吗？此操作不可撤销。')) return;

    try {
        const res = await fetch(`/admin/api/${type}/${id}`, {
            method: 'POST',
            headers: csrfHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify({ _method: 'DELETE' })
        });
        const data = await res.json();

        if (data.code === 200) {
            showToast('删除成功', 'success');
            setTimeout(() => location.reload(), 500);
        } else {
            showToast(data.message || '删除失败', 'error');
        }
    } catch (e) {
        showToast('操作失败', 'error');
    }
}

/* ==========================================
 * 4. 退出登录
 * ========================================== */
async function logout() {
    try {
        await fetch('/admin/api/auth/logout', { method: 'POST' });
    } catch (e) {}
    window.location.href = '/admin/login';
}

/* ==========================================
 * 5. Pjax 兼容
 * ========================================== */
document.addEventListener('pjax:complete', () => {
    // 重新初始化需要的组件
});

/* ==========================================
 * 6. 全局 401 拦截 — 登录过期自动跳转
 * ========================================== */
(function patchFetch() {
    const originalFetch = window.fetch;
    window.fetch = function(...args) {
        return originalFetch.apply(this, args).then(async (res) => {
            if (res.status === 401 && res.headers.get('content-type')?.includes('json')) {
                const clone = res.clone();
                const body = await clone.json();
                if (body.code === 401) {
                    window.location.href = '/admin/login';
                    return Promise.reject(new Error('unauthorized'));
                }
            }
            return res;
        });
    };
})();

/* ==========================================
 * 7. 键盘快捷键
 * ========================================== */
document.addEventListener('keydown', (e) => {
    // ESC 关闭模态框
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-backdrop:not(.hidden)').forEach(m => {
            m.classList.add('hidden');
            m.classList.remove('flex');
        });
    }
});
