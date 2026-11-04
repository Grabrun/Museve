# 暮想 Museve 深度审计报告

- 仓库: https://github.com/Grabrun/Museve (default 分支, 快照 2026-11-04)
- 规模: 40 个 PHP 文件 / 6,437 行核心代码 + 前端资源 (Alpine/Tailwind/TinyMCE/Pjax)
- 定位: 个人情感化纪念站 (回忆时间轴 / 悄悄话 / 文章) + 双角色后台 (admin/author)
- 审计方式: 全量人工代码走查 (路由、鉴权、API、渲染层、部署脚本、文档一致性), 非动态渗透

---

## 一、总体结论

工程质量在个人项目里属于上乘: 结构清晰、注释完善、PDO 全参数化、输出转义覆盖率高、角色数据隔离做得细致。
但存在 **1 个高危存储型 XSS**、**5 个"UI 说了但代码没做"的功能谎言**、若干中低危问题。
综合评分 (10 分制):

| 维度 | 分数 | 说明 |
|---|---|---|
| 架构 | 8.5 | 路由/API/后台分层干净, 无框架依赖, 单文件入口清晰 |
| 代码质量 | 8.0 | 注释与文档极佳; 但有复制粘贴残留、未接线的配置 |
| 安全性 | 5.5 | 一条高危 XSS 链路 + 认证 cookie 无 Secure + CDN 供应链裸奔 |
| 功能正确性 | 7.0 | 阅读页上/下篇导航 404; 多个设置项为摆设 |
| 可维护性 | 8.0 | 文档 (DESIGN/合规报告) 齐全; 无测试、无 CI、无 composer |

---

## 二、高危 (High)

### H1. 文章正文存储型 XSS (author → 全站访客 / 后台管理员)

**链路**:
1. `admin/api/articles.php` POST/PUT 服务端清洗只有 `strip_tags($content, $allowedTags)`;
   白名单包含 `<a>` `<img>` —— **strip_tags 不剥属性**, 所以
   `<img src=x onerror="...">`、`<a href="javascript:...">` 原样入库。
2. `sections/read.php:396` `<?= $article['content'] ?>` **原样输出** (富文本设计意图)。
3. 结果:
   - **author 角色**可投递含 payload 的 pending 文章; admin 点"发布"后,
     所有访问 `/read/{id}` 的访客浏览器执行该 payload (盗数据、钓鱼、挂马)。
   - 前端 `article-edit.php` 的 DOMPurify 过滤发生在**浏览器里**, 不是安全边界:
     author 直接对 API 发 JSON 即可绕过 (curl 一行)。
   - 客户端 DOMPurify 配置里 `ALLOWED_ATTR` 包含 `style`, 且 `<a href>` 未禁
     `javascript:` —— 即使走正常编辑器, `javascript:` 链接也能存下来
     (DOMPurify 默认 hook 会拦 javascript: URL, 但显式 allow-list 写法绕过了这点,
      属于"自己把闸门拆了")。

**修复 (按优先级)**:
- 服务端 strip_tags 之后补一层属性清洗: 丢弃全部 `on*=` 事件属性,
  `<a href>` 白名单化 (仅 http/https/相对路径), `<img src>` 同; 或直接引入
  htmlpurifier (composer) 做权威白名单净化。
- 给正文容器加 CSP 兜底: `style` 属性允许但禁 inline script 是 CSP 做不到的
  (inline handler 受 `script-src` 管), 至少加
  `Content-Security-Policy: default-src 'self'; img-src 'self' data:; script-src 'self' https://cdn.jsdelivr.net`
  之类, 让残余 XSS 在 admin/访客侧都降威力。
- 前端 DOMPurify 配置: 去掉 `style`, 保留默认 URL 净化 hook。

---

## 三、中危 (Medium)

### M1. 认证 Cookie 无 Secure, 无 HSTS
- `admin/api/auth.php:62` setcookie `museve_token` 只设了 `HttpOnly+SameSite=Strict`,
  **没设 `secure`**; `config.example.php` 里 `cookie_secure => false` 并注明
  "生产环境改为 true" —— 但 **`$config['security']` 整个块没有任何代码引用**
  (全库 grep 仅 config.example 自身命中): 改配置文件也不会生效, 纯装饰。
- `nginx.conf` 只 listen 80, 无 HSTS。
- 影响: 用户在 HTTP 下登录一次, token 明文过一次网络即失窃 (30min 滑动 + 可
  改用户/设置, 危害不小)。
- 修复: 生产环境全站 HTTPS + `secure` 标志 + HSTS; 把 security 配置块真正接进代码,
  或删掉别再骗人。

### M2. 默认凭据 admin/admin123 + IP 级锁定 = 自锁 + 爆破窗口
- 首启自动建 `admin/admin123` (bcrypt cost 12, 哈希层面不错), 无强制改密。
- `checkLoginLock` 按 **IP** 计 5 次失败/30min 锁 30 分钟: 攻击者对站点 IP
  灌 5 个错密码即可让站长自己被锁 30 分钟 (认证 DoS)。应按 "IP+账号" 双键,
  或改用账号级失败计数。
- 附带: `login_attempts` 表从不修剪, 只增不减。

### M3. upload.php 无 CSRF 校验 + TinyMCE 自动上传无 CSRF 头
- 其余 8 个 admin API 都做了 `verifyCsrfToken()`, 唯独 upload 没有
  (TinyMCE `images_upload_url` 自动上传也带不上 header)。
- 目前靠 `museve_token` 的 `SameSite=Strict` 兜底, 实际可利用性低,
  但安全模型不一致: 一旦 cookie 策略放宽 (M1 修 Secure 时), 上传即成为
  跨站原语。补一个 `X-CSRF-Token` 校验或在上传端点做 referer 同源检查。

### M4. 用户管理缺 "最后一位 admin" 保护 + 密码无强度约束
- `admin/api/users.php` PUT: admin 可把自己降级为 author, 之后全站无管理员
  (可登进后台但进不了 users/settings/logs, 且 API 侧 PUT 设置被 403) → 自锁。
- 创建/重置密码无最小长度/复杂度校验 (可建密码为 `1` 的账号)。
- 删用户不清理该用户 `cookie_token` (行删了无所谓), 但 `articles/memories/whispers`
  的 `author_id` 变悬空外键, 前台 LEFT JOIN 显示 "匿名" — 观感问题。

### M5. 里程碑 API 无角色门禁 (author 可 CRUD 全站开发历程)
- `admin/api/milestones.php` 只有 requireAuth, 没有 role 检查;
  后台菜单把 milestones 放在 admin-only 区, 但 API 对 author 敞开 → 权限模型不一致。
  要么加 admin 检查, 要么明确 author 可管理并在 UI 里体现。

### M6. 后台 API 无全局异常处理 → 错误页信息泄露
- 公开 `api/*.php` 包了 try/catch, 但 9 个 admin API 没有。
  例如 `memories` PUT 时 `event_time` 缺省为 null → `NOT NULL` 违例 →
  未捕获 PDOException; `display_errors=On` 时向攻击者回显 SQL 与文件路径。
  加 `set_exception_handler` 统一返回 500 JSON。

### M7. settings 后台"保存"是任意键写入, 且 5 个开关是装饰品
- `admin/api/settings.php` PUT 对 body 里每个 `key` 直接
  `INSERT ... ON DUPLICATE KEY UPDATE`, 无白名单 (admin 角色, 危害有限, 但属于
  坏味道: 一个被劫持的 admin session 可写入任意键)。
- 更实际的问题: 设置页提供的 5 个控件**全部不生效** —
  | 控件 | 实际控制行为 |
  |---|---|
  | upload_max_size / upload_allowed_types | upload.php 读的是 `includes/config.php`, 不读 DB 设置 |
  | login_token_expiry | 过期时间硬编码 `INTERVAL 30 MINUTE` |
  | cdn_prefix | head.php 的 cacheBust() 根本没用 cdn 前缀 |
  | maintenance_mode | 全库无任何维护模式逻辑 |
  用户在后台改了这些值, 界面无反馈也无效果 — **这是用户直接踩到的坑**, 建议
  要么接线要么藏掉。

---

## 四、低危 / 功能缺陷 (Low)

| # | 问题 | 位置 | 说明 |
|---|---|---|---|
| L1 | **上/下篇导航 404** | `sections/read.php:403,412,425,432` | 链接写成 `/read?id=N`, 而路由只接受 `/read/{id}` (index.php:48 正则 `^/read/(\d+)$`) → 点必 404。memory.php 用的是正确写法。 |
| L2 | 表定义复制粘贴残留 | `includes/connect.php:29-181` | `TABLE_DEFINITIONS` 里 `'milestones'` 键重复出现 4 次 (后值覆盖前值, 内容相同故无害), 且 memories/whispers/articles 块上方注释全写 "里程碑表"。 |
| L3 | 每请求跑 DDL | `connect.php:autoCreateTables` | 所有页面 (含公开 API) 每次执行 8 条 `CREATE TABLE IF NOT EXISTS` + settings COUNT; auth_helper 再对 login_attempts 跑一次 DDL。加 `SHOW TABLES LIKE` 缓存或只首启建表。 |
| L4 | 阅读时长公式不一致 | `api/articles.php` | 列表页 `CHAR_LENGTH(content)` (含 HTML 标签, 还会被多字节字符坑), 详情页 `mb_strlen(strip_tags())` → 列表页预估分钟数虚高且两处不一致。 |
| L5 | sitemap lastmod 退化 | `sitemap.php:54` | 文章未更新过 `updated_at` 为 NULL → `strtotime(null)` → lastmod=1970-01-01 (PHP 8.1+ 还有 deprecation)。 |
| L6 | whispers `created_at` 客户端可控 | `admin/api/whispers.php` POST/PUT | author 可填未来时间戳, 破坏公开流排序; 至少封顶到 NOW()。 |
| L7 | 登录验证码未实现 | README "登录验证码" / `ERR_CAPTCHA_ERROR=2002` | 登录页与 API 均无 captcha, 常量成孤儿; 文档承诺与实际不符 (配合 M2 的 5 次/IP 锁定, 爆破成本其实还行, 但文档不该写)。 |
| L8 | PHP 版本声明不符 | README 8.0+ vs `connect.php` 的 `: never` | `never` 是 8.1+ 关键字 (8.0 下按类名解析碰巧不炸, 但语义已偏); deploy.sh 指向 `php8.0-fpm.sock` — 生产到底 8.0 还是 8.1 建议统一写清。 |
| L9 | 部署脚本信息 | `scripts/deploy.sh` | 泄露生产域名与本机路径 (`memory.grabrun.top`, `/root/.openclaw/workspace`); `eval rsync` 用法粗; 未把 `nginx.conf`、`docs/` 从 webroot 同步里排除 → 线上任何人可 GET `/nginx.conf` 读源站配置。 |
| L10 | backup.sh | DB `root` + 空密码, 备份存于站点 `backups/` 目录 (gitignore 了, 但 deploy 只排除了 uploads) | 本地约定, 提醒生产改。 |
| L11 | finfo 缺失时降级无提示 | `admin/api/upload.php` | 无 fileinfo 扩展时 magic-bytes 校验静默跳过, 只剩扩展名白名单; 建议缺失时直接 501。 |
| L12 | 记忆页死代码 | `sections/memory.php:86` | 渲染 `$memory['content']`, memories 表没有该列, 永远空串。 |
| L13 | 登录 session 固定 | `auth.php` | 登录成功未 `session_regenerate_id()`; CSRF token 与 PHPSESSID 绑定, 固定危害有限, 补一行更稳。 |
| L14 | logout 无 CSRF | `auth.php` | 跨站可把受害者踢下线 (低危 nuisance)。 |

---

## 五、供应链 / 前端

| 项 | 状态 |
|---|---|
| Tailwind Play CDN (`cdn.tailwindcss.com`) | **官方明示勿用于生产**: 每页 JIT 编译、体积大、隐私外发 DOM; 应预编译为 style.css (仓库已有, 但被 CDN 覆盖配置了) |
| `alpinejs@3.x.x` / `@phosphor-icons/web` (无版本) / `tinymce@6` | 浮动版本 + 无 SRI integrity → CDN 被劫持即全站 XSS; 全部钉死版本 + 加 `integrity` |
| DOMPurify 3.0.6 / pjax 0.2.8 | 已钉死, 未加 SRI |
| 无 CSP, 无 HSTS, X-XSS-Protection (已废弃) | 见 M1 |

---

## 六、亮点 (值得保留的好实践)

- **全库无 SQL 注入**: 所有用户输入均走 PDO 参数化, 连 LIMIT/OFFSET 都 `(int)` 后拼接;
- 输出转义覆盖率高: 前台/后台 30+ 处 `htmlspecialchars`, 公开 section 的用户数据
  全部转义 (whispers/memories/settings/about_text), JS 侧 `escapeHtml` 一致;
- 密码 bcrypt cost 12; token `random_bytes(32)`; cookie HttpOnly+SameSite=Strict;
- CSRF 会话绑定 + `hash_equals`, 8/9 个 mutating 端点都校验;
- 上传: 扩展名白名单 + finfo magic-bytes + 随机重命名 + `move_uploaded_file`, 是教科书级;
- 角色数据隔离 (author 只见自己文章/回忆/悄悄话) 在 API 层用 `authorFilter` 一致实现;
- 文档体系完整 (设计系统、合规报告、迁移 SQL), 审计友好。

---

## 七、修复优先级 (按 ROI)

1. **H1** 服务端净化 article content (事件属性 + javascript: URL), 半天工作量, 消除最大风险;
2. **M1** HTTPS + `secure` cookie + HSTS, 同时把 `security` 配置块真正接线或删掉;
3. **L1** 阅读页导航 URL 改 `/read/{id}`, 一行;
4. **M7** 5 个假设置控件: 接线或移除 (建议先移除, 留 `maintenance_mode` 真做);
5. **M2** 锁定改 "IP+账号" 双键 + 默认密码首登强制修改;
6. 供应链钉版本 + SRI + 弃 Tailwind CDN;
7. admin API 全局异常处理 (M6);
8. 其余按表逐项。

> 备注: 本审计为静态走查; H1 已按 "author 直连 API + admin 发布 + 访客读取" 的最短
> 路径人工推演, 未做动态 POC。建议修复后对 `/read/{id}` 做一次带 payload 的验证。
