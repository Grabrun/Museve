<p align="center">
  <picture>
    <source media="(prefers-color-scheme: dark)" srcset="resources/images/logo-light.svg">
    <img alt="暮想 Museve" src="resources/images/logo.svg" width="320">
  </picture>
</p>

<p align="center">
  <em>在薄暮时分，温柔地想起。</em>
</p>

<p align="center">
  <a href="#"><img src="https://img.shields.io/badge/PHP-8.1%2B-777BB4?style=flat-square&logo=php" alt="PHP 8.1+"></a>
  <a href="#"><img src="https://img.shields.io/badge/MySQL-8.0%2B-4479A1?style=flat-square&logo=mysql" alt="MySQL 8.0+"></a>
  <a href="LICENSE"><img src="https://img.shields.io/badge/license-MIT-blue?style=flat-square" alt="MIT License"></a>
  <a href="https://github.com/Grabrun/Museve/releases"><img src="https://img.shields.io/github/v/release/Grabrun/Museve?style=flat-square" alt="Latest Release"></a>
</p>

<br>

**暮想 (Museve)** 是一处收藏时光碎片的私密空间——回忆时光轴、悄悄话气泡流、文章静谧阅读，每个像素都在轻声说：你的回忆，值得被温柔珍藏。🍂

无框架、免构建、单入口路由；前端依赖全部本地化，运行时只向 Google Fonts 取字体。

---

## 📋 目录

- [✨ 特性](#-特性)
- [🛠 技术栈](#-技术栈)
- [🚀 快速开始](#-快速开始)
- [📖 配置详解](#-配置详解)
- [🌐 Nginx 部署](#-nginx-部署)
- [🧱 前端资源构建](#-前端资源构建)
- [📦 打包部署包](#-打包部署包)
- [📁 目录结构](#-目录结构)
- [🛡 安全设计](#-安全设计)
- [🎨 品牌色彩](#-品牌色彩)
- [📊 设计系统](#-设计系统)
- [🤝 参与贡献](#-参与贡献)
- [📜 License](#-license)

---

## ✨ 特性

### 🕰️ 回忆时光轴
左右交替卡片布局，滚动淡入动画，按年份分组展示珍贵记忆。

### 💬 悄悄话气泡流
四色柔和气泡样式，支持署名，IntersectionObserver 触底自动加载历史记录。

### 📝 文章系统
TinyMCE 富文本编辑器，720px 沉浸式阅读宽度，关键词搜索，草稿 / 审核中 / 已发布三态流转。`author` 角色只能提审，`admin` 才能发布。

### 🔐 权限管理
`admin` / `author` 双角色体系，API 与页面层双重门禁；admin 专属页面对 author 直接 403。

### 🎨 情感化设计
- 🥃 毛玻璃质感 (Glassmorphism)
- ⌨️ 打字机引语动画
- 🌅 品牌暮光配色体系
- 🎬 滚动视差 + 淡入动画（IntersectionObserver + CSS，无动画库）

### 📱 响应式布局
移动端底部固定导航，桌面端顶部导航，Pjax 无刷新页面切换。

### 🔍 SEO
Open Graph + Twitter Card + 动态 Sitemap + 语义化 HTML5。

### 🛡️ 安全防护
服务端富文本白名单净化、CSRF Token、登录限速与锁定、CSP/HSTS、PDO 全参数化。详见[安全设计](#-安全设计)。

### 💾 数据备份
`scripts/backup.sh` 一键备份，默认保留 30 天，可直接挂定时任务。

### 🧩 免构建部署
Tailwind 构建期预编译，Alpine / Pjax / DOMPurify / TinyMCE / Phosphor 全部落在 `resources/vendor/`，**部署机不需要 Node**，运行时没有任何第三方 JS 请求。

---

## 🛠 技术栈

| 层级 | 选型 | 说明 |
|------|------|------|
| **后端** | PHP 8.1+ / MySQL 8.0+ | PDO 单例连接，运行时自建表 |
| **路由** | 自写单入口 | `index.php` 匹配路径，无框架依赖 |
| **样式** | Tailwind CSS v3 | 构建期预编译成 `tailwind.css`，非浏览器内 JIT |
| **交互** | 原生 JS + Pjax | 无刷新导航，保留浏览器历史 |
| **后台交互** | Alpine.js | 仅后台侧边栏使用，前台已移除 |
| **编辑器** | TinyMCE 6 | 富文本编辑，图片上传带 CSRF 头 |
| **净化** | DOMDocument（服务端） | 标签 / 属性 / 协议三级白名单 |
| **图标** | Phosphor Icons | 本地字体图标 |
| **字体** | Noto Serif SC / Inter / ZCOOL XiaoWei / Caveat | Google Fonts（唯一外部请求） |
| **动画** | IntersectionObserver + CSS | 无第三方动画库 |

---

## 🚀 快速开始

### 方式一：从源码

```bash
# 1. 克隆
git clone https://github.com/Grabrun/Museve.git
cd Museve

# 2. 配置数据库
cp includes/config.example.php includes/config.php
vim includes/config.php          # 填数据库连接信息

# 3. 权限（上传目录必须对 PHP-FPM 用户可写）
chown -R www:www .
chmod -R 775 uploads

# 4. 配置 Nginx
#    参考仓库根目录的 nginx.conf，见下方「Nginx 部署」

# 5. 访问首页：首次访问自动建表并初始化管理员
#    后台：https://your-domain/admin
#    默认账号：admin / admin123  ⚠️ 首次登录后请立即修改
```

> 依赖产物已提交在仓库里，**这一步不需要 `npm install`**。

### 方式二：用部署包

```bash
npm run pack                                   # 生成 dist/museve-<时间戳>-<sha>.zip
unzip dist/museve-*.zip -d /www/wwwroot/your-domain.com
cd /www/wwwroot/your-domain.com
cp includes/config.example.php includes/config.php && vim includes/config.php
chown -R www:www . && chmod -R 775 uploads
```

部署包只含运行期文件，剔除了配置、运维脚本、开发工具与文档。详见[打包部署包](#-打包部署包)。

### 环境要求

| 要求 | 最低版本 |
|------|----------|
| PHP | 8.1+ |
| MySQL | 8.0+ |
| Nginx / Apache | 支持 URL 重写 |
| PHP 扩展 | PDO、PDO_MySQL、JSON、MBString、DOM、Fileinfo |

> `Fileinfo` 用于上传的服务端 MIME 校验。缺失时上传接口会直接返回 501，而不是退化成信任客户端声明的类型。
> `DOM` 用于富文本净化，缺了它整个站点的文章渲染都不正常。

---

## 📖 配置详解

`includes/config.php` 只管**部署期**参数——数据库、目录、安全阈值。站点文案与功能开关存在数据库里，后台改、即时生效。

```php
return [
    // 调试开关：生产必须 false，否则错误堆栈会回显给访客
    'debug' => false,

    // 📍 数据库
    'db' => [
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'dbname'   => 'museve',
        'username' => 'root',
        'password' => '',
        'charset'  => 'utf8mb4',
    ],

    // 🌐 站点
    'site' => [
        'title'          => '暮想',
        'subtitle'       => '在薄暮时分，温柔地想起。',
        'logo'           => '/resources/images/logo.svg',
        'favicon'        => '/resources/images/favicon.png',
        'default_avatar' => '/resources/images/default-avatar.png',
    ],

    // 🔐 安全（以下参数均已接入代码）
    'security' => [
        'bcrypt_cost'           => 12,     // 口令哈希强度
        'login_max_attempts'    => 5,      // 单 IP+账号 失败上限
        'login_lockout_minutes' => 30,     // 锁定时长
        'cookie_httponly'       => true,
        'cookie_secure'         => false,  // 默认跟随协议自动判定 HTTPS
        'cookie_samesite'       => 'Strict',
    ],

    // 📤 上传（上限与类型以后台设置为准，这里只保留目录）
    'upload' => [
        'upload_dir' => __DIR__ . '/../uploads/',
    ],
];
```

### 后台设置速查

这些项存在 `settings` 表，改完立即生效，无需重启：

| 分组 | 设置项 |
|------|--------|
| 站点信息 | `site_title` `site_subtitle` `site_description` `site_keywords` |
| 视觉 | `site_logo` `site_avatar` `home_avatar` |
| 首页引语 | `quote_1` `quote_2` `quote_3` |
| 页脚 | `icp_number` `police_icp` `copyright` `custom_footer` |
| 关于页 | `about_text`（在「里程碑」页底部编辑） |
| 上传 | `upload_max_size` `upload_allowed_types` |
| 安全 | `login_token_expiry`（登录有效期，分钟） |
| 高级 | `cdn_prefix`（静态资源 CDN 前缀）、`maintenance_mode`（维护模式，管理员可绕过） |

---

## 🌐 Nginx 部署

完整配置见仓库根目录的 [`nginx.conf`](nginx.conf)。**这个文件不要放进网站根目录**，它应该在 `/etc/nginx/` 或面板的站点配置里。

三条最容易漏的：

1. **全站 HTTPS**。认证 Cookie 的 `Secure` 跟随协议自动开启，纯 HTTP 下等于没有；
2. **`fastcgi_param HTTPS $https if_not_empty;`** 必须加，否则 PHP 判定不出 HTTPS，Cookie 与 HSTS 都不会生效；
3. **`docs/`、`backups/`、`scripts/`、`tools/` 必须 deny**，以及 `*.md` `*.sql` `*.conf` 这类后缀——这些目录留在 webroot 就是公开可下载的。

```nginx
location ^~ /docs/    { deny all; }
location ^~ /backups/ { deny all; }
location ^~ /scripts/ { deny all; }
location ^~ /tools/   { deny all; }
location ~ ^/(includes|admin/api/auth_helper) { deny all; }
location ~* \.(md|conf|sql|sh|bak|ini|lock|log|swp|mjs|ts|map)$ { deny all; }
```

---

## 🧱 前端资源构建

构建产物**已提交到仓库**，所以部署不需要 Node。只有改了模板样式或升级依赖时才需要重建：

```bash
npm install            # 首次

npm run build          # 预编译 Tailwind + 同步 vendor 资源
npm run check:classes  # 校验模板里用到的 class 是否都进了构建产物
npm run lint:php       # PHP 语法检查
npm run verify         # 上面三步一起跑
```

**改了模板里的 class 必须重跑 `npm run build`**。Tailwind 已从浏览器内 JIT 改为构建期预编译，新加的 class 不跑构建就不会出现在 `tailwind.css` 里，页面上表现为样式丢失。`npm run check:classes` 就是用来兜这个坑的。

### 为什么提交构建产物

`resources/css/tailwind.css` 和 `resources/vendor/` 是 `npm run build` 的产物，但仍然入库——这样克隆下来直接能跑，不需要在服务器装 Node，也不用担心生产机编译出的 CSS 与本地不一致。升级依赖后记得重新构建并提交。

`.gitattributes` 已把文本统一为 LF，避免脚本在 Windows 检出后带到 Linux 变成 CRLF 而执行失败。

---

## 📦 打包部署包

```bash
npm run verify   # 先确保构建产物是最新的
npm run pack     # 输出 dist/museve-<YYYYMMDD-HHMMSS>-<git-sha>.zip
```

打包器按**白名单**收集（只允许 `index.php` `404.html` `robots.txt` `sitemap.php` 与 `admin/` `api/` `includes/` `sections/` `resources/` `tools/` `uploads/`），再叠黑名单剔除开发产物。任何不在白名单里的新文件默认进不去，不需要每次补规则。

内置预检：命中 `includes/config.php`、`node_modules`、`docs/`、`package.json`、`nginx.conf` 会直接拒绝出包。

包内**不含**：配置（含口令）、`nginx.conf` 与 `scripts/`（运维文件）、`docs/`（内部文档）、`node_modules/`、`tools/php/`、`*.mjs`、`tailwind.src.css`、`backups/`、所有 `*.md`。

---

## 📁 目录结构

```
Museve/
├── index.php                 # 前台单入口（路由 / Pjax / 维护模式）
├── 404.html                  # Nginx 层 404
├── robots.txt
├── sitemap.php               # 动态 sitemap
│
├── sections/                 # 前台页面（9 个）
│   ├── home.php  about.php   memories.php  memory.php
│   ├── whispers.php  articles.php  read.php
│   ├── maintenance.php       # 维护模式页
│   └── 404.php
│
├── api/                      # 前台公开只读接口（4 个）
│   ├── articles.php  memories.php  whispers.php  settings.php
│
├── admin/
│   ├── index.php  login.php
│   ├── sections/             # 后台页面：文章/编辑/回忆/里程碑/悄悄话/用户/设置/日志/首页
│   ├── api/                  # 后台接口：articles/auth/auth_helper/memories/
│   │                         #           milestones/settings/upload/users/whispers
│   └── includes/             # 后台头尾模板
│
├── includes/
│   ├── connect.php           # 配置加载、建表、工具函数、异常处理器、安全响应头
│   ├── html-sanitizer.php    # 富文本服务端白名单净化
│   ├── head.php  foot.php    # 前台头尾
│   └── config.example.php    # 配置模板（复制为 config.php）
│
├── resources/
│   ├── css/                  # style.css / admin.css / tailwind.css(构建产物)
│   ├── js/                   # main.js / admin.js
│   ├── images/
│   └── vendor/               # 本地化第三方库（构建产物）
│
├── tools/                    # 构建与维护脚本（Nginx 已 deny /tools/）
│   ├── pack.mjs              # 打包部署包
│   ├── sync-vendor.mjs       # 同步 vendor 资源
│   ├── check-classes.mjs     # Tailwind 类名覆盖检查
│   ├── lint-php.mjs          # PHP 语法检查
│   └── resanitize-articles.php  # 历史文章一次性净化
│
├── scripts/                  # 运维脚本（不进 webroot）
│   ├── deploy.sh             # 覆盖式同步部署
│   └── backup.sh             # 数据库备份 + 过期清理
│
├── uploads/                  # 上传目录（运行时可写）
├── docs/                     # 设计文档
├── nginx.conf                # Nginx 参考配置
├── package.json              # 仅开发期使用
└── LICENSE
```

---

## 🛡 安全设计

| 威胁 | 措施 |
|------|------|
| **存储型 XSS** | 富文本入库前经 `html-sanitizer.php`：标签白名单 + 属性白名单 + 协议白名单（`http`/`https`/`mailto`/`tel`/相对路径，`data:` 仅限图片），`on*` 事件一律剥离，`style` 按 CSS 属性过滤。输出侧再净化一次，覆盖历史数据 |
| **SQL 注入** | 全站 PDO 预处理，无字符串拼接 |
| **CSRF** | 所有写操作校验 token；登出同样校验 |
| **上传攻击** | 扩展名白名单 + 服务端 `finfo` 检测真实 MIME；缺 `fileinfo` 直接拒绝而非降级 |
| **会话劫持** | `HttpOnly` + `SameSite=Strict` + HTTPS 下 `Secure`；登录成功后 `session_regenerate_id` |
| **暴力破解** | 按 **IP + 账号** 双键计数，超限锁定；同 IP 总失败数另设上限；失败记录定期修剪，避免无限增长 |
| **越权** | `admin` / `author` 双角色，API 与页面层各自门禁；最后一位管理员不可降级或删除 |
| **信息泄露** | 生产关闭 `display_errors`；全局异常处理器统一兜底，不吐 SQL 与文件路径 |
| **响应头** | CSP、HSTS、`X-Frame-Options`、`X-Content-Type-Options`、`Referrer-Policy`、`Permissions-Policy` |

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

暮想遵循系统的设计规范，覆盖品牌、布局、组件、动效等八个维度。

| 系统 | 说明 |
|:-----|:-----|
| 🎨 品牌视觉系统 | 色彩、字体、Logo |
| 🧱 组件系统 | 卡片、按钮、表单 |
| 📐 布局系统 | 响应式网格 |
| ✨ 动效系统 | 滚动动画、过渡 |
| 🔐 权限系统 | 角色、CSRF、锁定 |
| 🌐 SEO 系统 | OGP、Sitemap |
| ⚡ 性能系统 | 缓存、懒加载 |
| 📦 部署系统 | 脚本、备份 |

> 完整设计文档见 [`docs/DESIGN.md`](docs/DESIGN.md) 与 [`docs/DESIGN-SYSTEMS.md`](docs/DESIGN-SYSTEMS.md)。

---

## 🤝 参与贡献

1. 🍴 Fork 本仓库
2. 🌿 创建特性分支：`git checkout -b feat/your-feature`
3. 📝 提交修改：`git commit -m 'feat: 添加xxx功能'`
4. 🚀 推送：`git push origin feat/your-feature`
5. 🔀 发起 Pull Request

改动模板或样式后请跑一遍 `npm run verify` 再提交。

### 提交规范

遵循 [Conventional Commits](https://www.conventionalcommits.org/)：

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
