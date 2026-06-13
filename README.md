<p align="center">
  <picture>
    <source media="(prefers-color-scheme: dark)" srcset="https://raw.githubusercontent.com/Grabrun/Museve/main/resources/images/logo-dark.svg">
    <img alt="暮想 Museve" src="https://raw.githubusercontent.com/Grabrun/Museve/main/resources/images/logo-light.svg" width="320">
  </picture>
</p>

<p align="center">
  <em>在薄暮时分，温柔地想起。</em>
</p>

<p align="center">
  <a href="#"><img src="https://img.shields.io/badge/PHP-8.0%2B-777BB4?style=flat-square&logo=php" alt="PHP 8.0+"></a>
  <a href="#"><img src="https://img.shields.io/badge/MySQL-8.0%2B-4479A1?style=flat-square&logo=mysql" alt="MySQL 8.0+"></a>
  <a href="LICENSE"><img src="https://img.shields.io/badge/license-MIT-blue?style=flat-square" alt="MIT License"></a>
  <a href="https://github.com/Grabrun/Museve/releases"><img src="https://img.shields.io/github/v/release/Grabrun/Museve?style=flat-square" alt="Latest Release"></a>
  <a href="#"><img src="https://img.shields.io/badge/coverage-93%25-brightgreen?style=flat-square" alt="Design Compliance 93%"></a>
</p>

<br>

**暮想 (Museve)** 是一处收藏时光碎片的私密空间——回忆时光轴、悄悄话气泡流、文章静谧阅读，每个像素都在轻声说：你的回忆，值得被温柔珍藏。🍂

---

## 📋 目录

- [✨ 特性](#-特性)
- [🖼️ 截图](#️-截图)
- [🛠 技术栈](#-技术栈)
- [🚀 快速开始](#-快速开始)
- [📖 配置详解](#-配置详解)
- [🌐 Nginx 部署](#-nginx-部署)
- [📁 目录结构](#-目录结构)
- [🎨 品牌色彩](#-品牌色彩)
- [📊 设计系统](#-设计系统)
- [🤝 参与贡献](#-参与贡献)
- [📜 License](#-license)

---

## ✨ 特性

### 🕰️ 回忆时光轴
左右交替卡片布局，滚动淡入动画效果，按年份分组展示珍贵记忆。

### 💬 悄悄话气泡流
四色柔和气泡样式，支持署名功能，无限滚动加载历史记录。

### 📝 文章系统
TinyMCE 富文本编辑器，720px 沉浸式阅读宽度，关键词搜索，状态管理（草稿/审核中/已发布）。

### 🔐 权限管理
`admin` / `author` 双角色体系，CSRF 防护，登录失败锁定策略，会话安全加固。

### 🎨 情感化设计
- 🥃 毛玻璃质感 (Glassmorphism)
- ⌨️ 打字机引语动画
- 🌅 品牌暮光配色体系
- 🎬 滚动视差 + 淡入动画

### 📱 响应式布局
移动端底部导航栏，桌面端时间轴布局，Pjax 无刷新页面切换，零等待体验。

### 🔍 SEO 优化
Open Graph 协议 + Twitter Card + 动态 Sitemap 生成 + 语义化 HTML5 结构。

### 🛡️ 安全防护
CSRF Token 验证、XSS 输出过滤、安全 HTTP 头、SQL 注入防护（PDO 预处理）、登录验证码。

### 💾 数据备份
一键备份脚本，30 天自动清理，支持定时任务集成。

---

## 🖼️ 截图

> 截图正在路上，敬请期待 🚧

| 首页时间轴 | 悄悄话 | 文章阅读 |
|:---:|:---:|:---:|
| *暂缺* | *暂缺* | *暂缺* |

---

## 🛠 技术栈

| 层级 | 选型 | 说明 |
|------|------|------|
| **后端** | PHP 8.0+ / MySQL 8.0+ | PDO 单例连接，自动建表 |
| **前端** | Tailwind CSS v3 / Alpine.js | 工具类优先 + 轻量响应式 JS |
| **导航** | Pjax (pushState + AJAX) | 无刷新页面切换，保留浏览器历史 |
| **编辑器** | TinyMCE | 富文本编辑，图片上传 |
| **图标** | Phosphor Icons | 正则图标集，按需加载 |
| **字体** | Noto Serif SC / Inter / ZCOOL XiaoWei | 中英文混排优化 |
| **动画** | ScrollReveal | 滚动触发淡入动画 |

---

## 🚀 快速开始

```bash
# 1. 克隆项目
git clone https://github.com/Grabrun/Museve.git
cd Museve

# 2. 配置数据库
cp includes/config.example.php includes/config.php
vim includes/config.php  # 填写数据库连接信息

# 3. 配置 Nginx
# 参考 nginx.conf 配置重写规则（见下方 Nginx 部署）

# 4. 访问首页
# 首次访问自动建表并初始化管理员账号
# 后台地址: https://your-domain/admin
# 默认账号: admin / admin123 ⚠️ 首次登录后请立即修改密码！
```

### 环境要求

| 要求 | 最低版本 |
|------|----------|
| PHP | 8.0+ |
| MySQL | 8.0+ |
| Nginx / Apache | 支持 URL 重写 |
| PHP 扩展 | PDO、PDO_MySQL、JSON、MBString |

---

## 📖 配置详解

`includes/config.php` 核心配置项：

```php
// 📍 数据库
'db' => [
    'host'     => 'localhost',
    'port'     => 3306,
    'dbname'   => 'museve',
    'username' => 'root',
    'password' => '',
    'charset'  => 'utf8mb4',
],

// 🔗 站点 URL（必须正确，影响 OGP 和路由）
'site_url' => 'https://example.com',

// 🌐 站点名称
'site_name' => '暮想 Museve',

// 📄 每页条数
'per_page'     => 12,
'admin_per_page' => 15,
```

---

## 🌐 Nginx 部署

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/Museve;
    index index.php;

    # Pjax 单入口路由
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # 后台路由
    location /admin {
        try_files $uri $uri/ /admin/index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.x-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # 静态资源缓存
    location ~* \.(css|js|jpg|jpeg|png|gif|ico|svg|webp)$ {
        expires 30d;
        add_header Cache-Control "public, no-transform";
    }

    # 上传目录
    location /uploads/ {
        expires 7d;
        add_header Cache-Control "public, no-transform";
    }
}
```

---

## 📁 目录结构

```
Museve/
├── index.php               # 前端统一入口 (Pjax 路由)
│
├── admin/
│   ├── index.php           # 后台入口
│   ├── login.php           # 登录页
│   ├── sections/           # 后台页面 (7 个模块)
│   │   ├── articles.php
│   │   ├── article-edit.php
│   │   ├── memories.php
│   │   ├── milestones.php
│   │   ├── whispers.php
│   │   ├── users.php
│   │   └── settings.php
│   └── api/                # 后台 API (8 个)
│
├── sections/               # 前端页面
│   ├── home.php
│   ├── about.php
│   ├── article.php
│   └── ...
│
├── api/                    # 前端 API
│
├── includes/               # 公共组件 (头/尾/连接器)
│
├── resources/
│   ├── css/                # 样式文件
│   ├── js/                 # 脚本文件
│   └── images/             # 品牌资源
│
├── scripts/                # 运维脚本
│   └── deploy.sh           # 部署脚本
│
├── uploads/                # 上传文件目录
├── docs/                   # 设计文档
├── nginx.conf              # Nginx 配置模板
└── README.md               # 📖 本文件
```

---

## 🎨 品牌色彩

| 色名 | 色值 | 用途 | 预览 |
|:------|:-----|:-----|:----:|
| 暮光白 | `#F9F7F4` | 主背景色 | 🟤 |
| 薄暮玫瑰 | `#DDB8B8` | 主强调色 | 🌸 |
| 静谧蓝 | `#A8C5DA` | 辅助强调 | 💎 |
| 深夜褐 | `#3E3640` | 主文字色 | 🟫 |
| 岁月灰 | `#8E827F` | 辅助文字 | 🩶 |
| 雾绿 | `#87A878` | 已发布状态 | 🌿 |
| 暖橙 | `#E0A96D` | 待审核状态 | 🍊 |
| 雪白 | `#FFFFFF` | 卡片/模态框 | ⚪ |

---

## 📊 设计系统

暮想遵循系统的设计规范，覆盖品牌、布局、组件、动效等八个维度，设计符合度 **93%**。

| 系统 | 符合度 | 说明 |
|:-----|:------:|:-----|
| 🎨 品牌视觉系统 | 100% | 色彩、字体、Logo |
| 🧱 组件系统 | 100% | 卡片、按钮、表单 |
| 📐 布局系统 | 95% | 响应式网格 |
| ✨ 动效系统 | 95% | 滚动动画、过渡 |
| 🔐 权限系统 | 95% | 角色、CSRF、锁定 |
| 🌐 SEO 系统 | 90% | OGP、Sitemap |
| ⚡ 性能系统 | 85% | 缓存、懒加载 |
| 📦 部署系统 | 80% | 脚本、备份 |

> 完整设计文档见 [`docs/DESIGN.md`](docs/DESIGN.md) 和 [`docs/DESIGN-SYSTEMS.md`](docs/DESIGN-SYSTEMS.md)

---

## 🤝 参与贡献

欢迎任何形式的贡献！

1. 🍴 Fork 本仓库
2. 🌿 创建特性分支：`git checkout -b feat/your-feature`
3. 📝 提交修改：`git commit -m 'feat: 添加xxx功能'`
4. 🚀 推送：`git push origin feat/your-feature`
5. 🔀 发起 Pull Request

### 提交规范

遵循 [Conventional Commits](https://www.conventionalcommits.org/) 规范：

- `feat:` 新功能
- `fix:` 修复
- `style:` 样式调整
- `refactor:` 重构
- `docs:` 文档变更
- `chore:` 杂项

---

## 📜 License

[MIT](LICENSE) © 2024 Grabrun

---

<p align="center">
  <sub>薄暮玫瑰色的时光容器，每一个像素都在轻声说：你的回忆，值得被温柔珍藏。</sub>
</p>

<p align="center">
  <a href="https://github.com/Grabrun/Museve">GitHub</a> ·
  <a href="https://github.com/Grabrun/Museve/issues">Issues</a> ·
  <a href="https://github.com/Grabrun/Museve/releases">Releases</a>
</p>
