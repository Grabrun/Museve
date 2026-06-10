/**
 * 暮色 Museve — 后台管理脚本
 * 侧边栏 · 表格选择 · 模态框 · 文件上传 · 自动保存 · Toast · TinyMCE
 */

/* ==========================================
 * 1. 侧边栏折叠 (移动端汉堡菜单)
 * ========================================== */
const AdminSidebar = {
  init() {
    this.sidebar = document.querySelector('.admin-sidebar');
    this.burger = document.querySelector('.admin-topbar__burger');
    this.overlay = document.querySelector('.sidebar-overlay');

    if (this.burger) {
      this.burger.addEventListener('click', () => this.toggle());
    }
    if (this.overlay) {
      this.overlay.addEventListener('click', () => this.close());
    }
  },

  toggle() {
    this.sidebar?.classList.toggle('open');
    this.overlay?.classList.toggle('active');
  },

  close() {
    this.sidebar?.classList.remove('open');
    this.overlay?.classList.remove('active');
  },
};

/* ==========================================
 * 2. 表格行选择 (checkbox 全选/反选)
 * ========================================== */
const TableSelect = {
  init() {
    this.selectAll = document.querySelector('#select-all');
    this.checkboxes = document.querySelectorAll('.row-select');

    if (this.selectAll) {
      this.selectAll.addEventListener('change', () => this._toggleAll());
    }

    this.checkboxes.forEach((cb) => {
      cb.addEventListener('change', () => this._updateSelectAll());
    });
  },

  _toggleAll() {
    const checked = this.selectAll.checked;
    this.checkboxes.forEach((cb) => {
      cb.checked = checked;
      cb.closest('tr')?.classList.toggle('selected', checked);
    });
  },

  _updateSelectAll() {
    const total = this.checkboxes.length;
    const selected = document.querySelectorAll('.row-select:checked').length;
    if (this.selectAll) {
      this.selectAll.checked = total > 0 && selected === total;
      this.selectAll.indeterminate = selected > 0 && selected < total;
    }
  },

  getSelectedIds() {
    const ids = [];
    document.querySelectorAll('.row-select:checked').forEach((cb) => {
      const id = cb.dataset.id || cb.value;
      if (id) ids.push(id);
    });
    return ids;
  },
};

/* ==========================================
 * 3. 删除确认模态框
 * ========================================== */
const DeleteModal = {
  init() {
    this.overlay = document.querySelector('#delete-modal');
    this.nameEl = this.overlay?.querySelector('.modal__name');
    this.confirmBtn = this.overlay?.querySelector('.modal__confirm');
    this.cancelBtn = this.overlay?.querySelector('.modal__cancel');

    if (this.cancelBtn) {
      this.cancelBtn.addEventListener('click', () => this.hide());
    }
    if (this.overlay) {
      this.overlay.addEventListener('click', (e) => {
        if (e.target === this.overlay) this.hide();
      });
    }
  },

  show(id, name, endpoint) {
    this._currentId = id;
    this._endpoint = endpoint;

    if (this.nameEl) this.nameEl.textContent = name || '';
    this.overlay?.classList.add('active');

    if (this.confirmBtn) {
      // 移除旧监听器
      const newBtn = this.confirmBtn.cloneNode(true);
      this.confirmBtn.replaceWith(newBtn);
      this.confirmBtn = newBtn;
      this.confirmBtn.addEventListener('click', () => this._confirm());
    }
  },

  hide() {
    this.overlay?.classList.remove('active');
  },

  async _confirm() {
    if (!this._endpoint || !this._currentId) return;

    try {
      const res = await fetch(`${this._endpoint}/${this._currentId}`, {
        method: 'DELETE',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
      });

      if (res.ok) {
        Toast.success('删除成功');
        // 移除对应行
        const row = document.querySelector(`tr[data-id="${this._currentId}"]`);
        if (row) {
          row.style.transition = 'opacity 0.3s';
          row.style.opacity = '0';
          setTimeout(() => row.remove(), 300);
        }
      } else {
        Toast.error('删除失败，请重试');
      }
    } catch (err) {
      Toast.error('网络错误');
    }

    this.hide();
  },
};

/* ==========================================
 * 4. 文件上传预览 (drag & drop + 点击)
 * ========================================== */
const FileUpload = {
  init() {
    document.querySelectorAll('.upload-zone').forEach((zone) => this._bindZone(zone));
  },

  _bindZone(zone) {
    const input = zone.querySelector('input[type="file"]');
    const preview = zone.querySelector('.upload-zone__preview');

    // 点击上传
    zone.addEventListener('click', () => input?.click());

    // 拖拽样式
    ['dragenter', 'dragover'].forEach((evt) => {
      zone.addEventListener(evt, (e) => {
        e.preventDefault();
        zone.classList.add('dragover');
      });
    });

    ['dragleave', 'drop'].forEach((evt) => {
      zone.addEventListener(evt, (e) => {
        e.preventDefault();
        zone.classList.remove('dragover');
      });
    });

    // 拖拽上传
    zone.addEventListener('drop', (e) => {
      const file = e.dataTransfer?.files[0];
      if (file) {
        this._previewFile(file, preview);
        if (input) {
          const dt = new DataTransfer();
          dt.items.add(file);
          input.files = dt.files;
        }
      }
    });

    // 选择文件
    if (input) {
      input.addEventListener('change', () => {
        const file = input.files[0];
        if (file) this._previewFile(file, preview);
      });
    }
  },

  _previewFile(file, preview) {
    if (!preview || !file.type.startsWith('image/')) return;

    const reader = new FileReader();
    reader.onload = (e) => {
      preview.src = e.target.result;
      preview.classList.add('visible');
    };
    reader.readAsDataURL(file);
  },
};

/* ==========================================
 * 5. 表单自动保存 (localStorage)
 * ========================================== */
const AutoSave = {
  _timer: null,
  _formId: null,

  init(formId = 'auto-save-form') {
    this._formId = formId;
    const form = document.getElementById(formId);
    if (!form) return;

    // 恢复
    this._restore(form);

    // 监听
    form.addEventListener('input', () => {
      clearTimeout(this._timer);
      this._timer = setTimeout(() => this._save(form), 2000);
    });

    // 提交后清除
    form.addEventListener('submit', () => {
      localStorage.removeItem(this._storageKey());
    });
  },

  _storageKey() {
    return `museve_autosave_${this._formId}`;
  },

  _save(form) {
    const data = {};
    const fields = form.querySelectorAll('input[name], textarea[name], select[name]');

    fields.forEach((field) => {
      if (field.type === 'file') return;
      if (field.type === 'checkbox') {
        data[field.name] = field.checked;
      } else {
        data[field.name] = field.value;
      }
    });

    localStorage.setItem(this._storageKey(), JSON.stringify(data));
    Toast.info('草稿已自动保存');
  },

  _restore(form) {
    try {
      const raw = localStorage.getItem(this._storageKey());
      if (!raw) return;
      const data = JSON.parse(raw);

      Object.entries(data).forEach(([name, value]) => {
        const field = form.querySelector(`[name="${name}"]`);
        if (!field) return;
        if (field.type === 'checkbox') {
          field.checked = value;
        } else {
          field.value = value;
        }
      });
    } catch (e) {
      // ignore
    }
  },

  clear() {
    localStorage.removeItem(this._storageKey());
  },
};

/* ==========================================
 * 6. Toast 通知
 * ========================================== */
const Toast = {
  _container: null,

  _getContainer() {
    if (!this._container) {
      this._container = document.querySelector('.toast-container');
      if (!this._container) {
        this._container = document.createElement('div');
        this._container.className = 'toast-container';
        document.body.appendChild(this._container);
      }
    }
    return this._container;
  },

  show(message, type = 'info', duration = 3000) {
    const container = this._getContainer();

    const icons = {
      success: '✓',
      error: '✕',
      warning: '⚠',
      info: 'ℹ',
    };

    const toast = document.createElement('div');
    toast.className = `toast toast--${type}`;
    toast.innerHTML = `
      <span class="toast__icon">${icons[type] || icons.info}</span>
      <span class="toast__message">${message}</span>
    `;

    container.appendChild(toast);

    // 触发动画
    requestAnimationFrame(() => {
      toast.classList.add('show');
    });

    // 自动消失
    setTimeout(() => {
      toast.classList.remove('show');
      toast.addEventListener('transitionend', () => toast.remove(), { once: true });
    }, duration);
  },

  success(msg) { this.show(msg, 'success'); },
  error(msg) { this.show(msg, 'error'); },
  warning(msg) { this.show(msg, 'warning'); },
  info(msg) { this.show(msg, 'info'); },
};

/* ==========================================
 * 7. TinyMCE 初始化
 * ========================================== */
function initTinyMCE(selector = '#article-editor') {
  if (typeof tinymce === 'undefined') return;

  tinymce.init({
    selector,
    skin: 'museve',
    height: 500,
    menubar: false,
    branding: false,
    language: 'zh_CN',
    plugins: [
      'advlist', 'autolink', 'lists', 'link', 'image', 'charmap',
      'preview', 'anchor', 'searchreplace', 'visualblocks', 'code',
      'fullscreen', 'insertdatetime', 'media', 'table', 'wordcount',
      'codesample', 'emoticons',
    ],
    toolbar: [
      'undo redo | blocks | bold italic underline strikethrough | forecolor backcolor',
      'alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media | codesample blockquote | removeformat code fullscreen',
    ],
    content_style: `
      @import url('https://fonts.googleapis.com/css2?family=Noto+Serif+SC:wght@400;600;700&display=swap');
      body {
        font-family: 'Noto Serif SC', serif;
        color: #3E3640;
        line-height: 1.8;
        padding: 16px;
      }
      img { max-width: 100%; height: auto; border-radius: 8px; }
      blockquote { border-left: 3px solid #DDB8B8; padding-left: 16px; color: #8E827F; }
    `,
    images_upload_handler: async (blobInfo) => {
      const formData = new FormData();
      formData.append('file', blobInfo.blob(), blobInfo.filename());

      const res = await fetch('/admin/api/upload', {
        method: 'POST',
        credentials: 'same-origin',
        body: formData,
      });

      if (!res.ok) throw new Error('上传失败');

      const data = await res.json();
      return data.url;
    },
    setup: (editor) => {
      editor.on('change', () => {
        editor.save();
      });
    },
  });
}

/* ==========================================
 * 8. 状态切换 (卡片式选择器)
 * ========================================== */
const StatusSelector = {
  init() {
    document.querySelectorAll('.status-selector').forEach((selector) => {
      const hiddenInput = selector.querySelector('input[type="hidden"]');
      const options = selector.querySelectorAll('.status-option');

      options.forEach((opt) => {
        opt.addEventListener('click', () => {
          options.forEach((o) => o.classList.remove('active'));
          opt.classList.add('active');
          if (hiddenInput) {
            hiddenInput.value = opt.dataset.value;
          }
          // 触发 change 事件
          hiddenInput?.dispatchEvent(new Event('change', { bubbles: true }));
        });
      });
    });
  },
};

/* ==========================================
 * 9. 搜索过滤 (实时过滤表格行)
 * ========================================== */
const TableSearch = {
  init() {
    document.querySelectorAll('.search-box__input[data-table]').forEach((input) => {
      const tableId = input.dataset.table;
      const table = document.getElementById(tableId);
      if (!table) return;

      input.addEventListener('input', () => {
        const query = input.value.toLowerCase().trim();
        const rows = table.querySelectorAll('tbody tr');

        rows.forEach((row) => {
          const text = row.textContent.toLowerCase();
          row.style.display = text.includes(query) ? '' : 'none';
        });
      });
    });
  },
};

/* ==========================================
 * 10. 分页
 * ========================================== */
const Pagination = {
  init() {
    document.querySelectorAll('.pagination__item[data-page]').forEach((btn) => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        const page = btn.dataset.page;
        if (btn.disabled || btn.classList.contains('active')) return;
        this._loadPage(page);
      });
    });
  },

  async _loadPage(page) {
    const container = document.querySelector('#paginated-content');
    const endpoint = container?.dataset.endpoint;
    if (!endpoint) return;

    try {
      const url = new URL(endpoint, window.location.origin);
      url.searchParams.set('page', page);

      const res = await fetch(url.toString(), {
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      });

      if (!res.ok) throw new Error(res.statusText);

      const data = await res.json();

      if (data.html && container) {
        container.innerHTML = data.html;
        // 重新绑定
        TableSelect.init();
        this.init();
      }

      // 更新 URL
      const currentUrl = new URL(window.location);
      currentUrl.searchParams.set('page', page);
      window.history.replaceState({}, '', currentUrl);
    } catch (err) {
      Toast.error('加载失败，请重试');
    }
  },
};

/* ==========================================
 * 初始化
 * ========================================== */
document.addEventListener('DOMContentLoaded', () => {
  AdminSidebar.init();
  TableSelect.init();
  DeleteModal.init();
  FileUpload.init();
  AutoSave.init();
  StatusSelector.init();
  TableSearch.init();
  Pagination.init();
  initTinyMCE();
});
