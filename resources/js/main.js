/**
 * 暮色 Museve — 前端主脚本
 * Pjax · 打字机 · 滚动动画 · 懒加载 · 导航高亮
 */

/* ==========================================
 * 1. Pjax 初始化 + 内容过渡
 * ========================================== */
(function initPjax() {
  if (typeof Pjax === 'undefined') return;

  const pjax = new Pjax({
    selectors: ['#museve-content'],
    cacheBust: false,
  });

  document.addEventListener('pjax:send', () => {
    const content = document.querySelector('#museve-content');
    if (content) {
      content.classList.add('pjax-fade-out');
    }
  });

  document.addEventListener('pjax:complete', () => {
    const content = document.querySelector('#museve-content');
    if (content) {
      content.classList.remove('pjax-fade-out');
      content.classList.add('pjax-fade-in');
      // 移除动画类以便下次使用
      content.addEventListener('animationend', () => {
        content.classList.remove('pjax-fade-in');
      }, { once: true });
    }
    // 重新初始化依赖
    initScrollAnimations();
    highlightNav();
    initLazyLoad();
  });
})();

/* ==========================================
 * 2. 打字机效果
 * ========================================== */
class Typewriter {
  /**
   * @param {HTMLElement} el - 目标元素
   * @param {string[]} strings - 要展示的字符串数组
   * @param {Object} [opts]
   * @param {number} [opts.typeSpeed=80] - 打字速度(ms)
   * @param {number} [opts.deleteSpeed=40] - 删除速度(ms)
   * @param {number} [opts.pauseMs=8000] - 切换间隔(ms)
   * @param {number} [opts.deleteDelay=1500] - 删除前停留(ms)
   */
  constructor(el, strings, opts = {}) {
    this.el = el;
    this.strings = strings;
    this.typeSpeed = opts.typeSpeed || 80;
    this.deleteSpeed = opts.deleteSpeed || 40;
    this.pauseMs = opts.pauseMs || 8000;
    this.deleteDelay = opts.deleteDelay || 1500;

    this.stringIdx = 0;
    this.charIdx = 0;
    this.isDeleting = false;

    // 创建光标
    this.cursor = document.createElement('span');
    this.cursor.className = 'typewriter-cursor';
    el.after(this.cursor);

    this._tick();
  }

  _tick() {
    const current = this.strings[this.stringIdx];

    if (!this.isDeleting) {
      // 打字
      this.charIdx++;
      this.el.textContent = current.substring(0, this.charIdx);

      if (this.charIdx === current.length) {
        // 打完一个词，等待后开始删除
        setTimeout(() => {
          this.isDeleting = true;
          this._tick();
        }, this.deleteDelay);
        return;
      }

      setTimeout(() => this._tick(), this.typeSpeed);
    } else {
      // 删除
      this.charIdx--;
      this.el.textContent = current.substring(0, this.charIdx);

      if (this.charIdx === 0) {
        this.isDeleting = false;
        this.stringIdx = (this.stringIdx + 1) % this.strings.length;
        setTimeout(() => this._tick(), 400);
        return;
      }

      setTimeout(() => this._tick(), this.deleteSpeed);
    }
  }
}

/* ==========================================
 * 3. 滚动动画 (IntersectionObserver)
 * ========================================== */
function initScrollAnimations() {
  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
          observer.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.15, rootMargin: '0px 0px -40px 0px' }
  );

  document.querySelectorAll('.scroll-animate, .timeline__content').forEach((el) => {
    if (!el.classList.contains('visible')) {
      observer.observe(el);
    }
  });
}

/* ==========================================
 * 4. 数字统计动画
 * ========================================== */
function animateCounter(el, target, duration = 1500) {
  const start = performance.now();
  const startVal = 0;

  function easeOutCubic(t) {
    return 1 - Math.pow(1 - t, 3);
  }

  function update(now) {
    const elapsed = now - start;
    const progress = Math.min(elapsed / duration, 1);
    const eased = easeOutCubic(progress);
    const current = Math.round(startVal + (target - startVal) * eased);

    el.textContent = current.toLocaleString();

    if (progress < 1) {
      requestAnimationFrame(update);
    }
  }

  requestAnimationFrame(update);
}

function initCounters() {
  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          const target = parseInt(entry.target.dataset.count, 10);
          if (!isNaN(target)) {
            animateCounter(entry.target, target);
          }
          observer.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.5 }
  );

  document.querySelectorAll('[data-count]').forEach((el) => {
    observer.observe(el);
  });
}

/* ==========================================
 * 5. 悄悄话无限滚动
 * ========================================== */
function initInfiniteScroll() {
  const container = document.querySelector('#whisper-list');
  const loadMore = document.querySelector('#whisper-load-more');
  if (!container || !loadMore) return;

  let page = parseInt(loadMore.dataset.page || '1', 10);
  let loading = false;
  const endpoint = loadMore.dataset.endpoint || '/api/whispers';

  const sentinel = document.createElement('div');
  sentinel.id = 'scroll-sentinel';
  container.after(sentinel);

  const observer = new IntersectionObserver(
    async (entries) => {
      if (!entries[0].isIntersecting || loading) return;
      loading = true;
      page++;

      try {
        const res = await fetch(`${endpoint}?page=${page}`);
        if (!res.ok) throw new Error(res.statusText);
        const data = await res.json();

        if (data.html) {
          container.insertAdjacentHTML('beforeend', data.html);
        }

        if (!data.has_more) {
          observer.disconnect();
          loadMore.remove();
        } else {
          loadMore.dataset.page = page;
        }
      } catch (err) {
        console.error('加载悄悄话失败:', err);
      } finally {
        loading = false;
      }
    },
    { rootMargin: '200px' }
  );

  observer.observe(sentinel);
}

/* ==========================================
 * 6. 图片懒加载
 * ========================================== */
function initLazyLoad() {
  if ('loading' in HTMLImageElement.prototype) {
    // 浏览器原生支持
    document.querySelectorAll('img[data-src]').forEach((img) => {
      img.src = img.dataset.src;
      img.removeAttribute('data-src');
    });
    return;
  }

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          const img = entry.target;
          img.src = img.dataset.src;
          img.removeAttribute('data-src');
          observer.unobserve(img);
        }
      });
    },
    { rootMargin: '100px' }
  );

  document.querySelectorAll('img[data-src]').forEach((img) => {
    observer.observe(img);
  });
}

/* ==========================================
 * 7. 导航高亮
 * ========================================== */
function highlightNav() {
  const currentPath = window.location.pathname;
  document.querySelectorAll('.nav-link').forEach((link) => {
    const href = link.getAttribute('href');
    if (!href) return;

    const isActive =
      (href === '/' && currentPath === '/') ||
      (href !== '/' && currentPath.startsWith(href));

    link.classList.toggle('active', isActive);
  });
}

/* ==========================================
 * 8. 移动端菜单 (Alpine.js)
 * ========================================== */
document.addEventListener('alpine:init', () => {
  Alpine.data('mobileMenu', () => ({
    open: false,
    toggle() {
      this.open = !this.open;
    },
    close() {
      this.open = false;
    },
  }));
});

/* ==========================================
 * 9. 退出登录
 * ========================================== */
function logout(e) {
  if (e) e.preventDefault();

  fetch('/admin/api/auth/logout', {
    method: 'POST',
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json' },
  })
    .then((res) => {
      if (res.ok) {
        window.location.href = '/';
      }
    })
    .catch((err) => {
      console.error('退出失败:', err);
    });
}

/* ==========================================
 * 初始化
 * ========================================== */
document.addEventListener('DOMContentLoaded', () => {
  initScrollAnimations();
  initCounters();
  initInfiniteScroll();
  initLazyLoad();
  highlightNav();
});
