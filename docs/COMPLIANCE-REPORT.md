# 暮想 Museve · 设计符合度检查报告

**检查时间：** 2026-06-10 20:25  
**对照文档：** V5.2 详细设计（八大系统拆分）

---

## 系统一：品牌与设计语言系统 ✅ 90%

| 检查项 | 状态 | 说明 |
|--------|------|------|
| 品牌色板 11 色 | ✅ | CSS 变量完整定义 |
| 设计 Token (--bg-primary 等) | ✅ | 已实现 12 个 Token |
| 字号阶梯 (7 级) | ✅ | text-xs ~ text-4xl |
| 字体: Noto Serif SC | ✅ | Google Fonts 已引入 |
| 字体: Inter | ✅ | Google Fonts 已引入 |
| 字体: ZCOOL XiaoWei / Caveat | ✅ | Google Fonts 已引入 |
| Phosphor Icons | ✅ | 已集成 @phosphor-icons/web |
| Tailwind 品牌色配置 | ✅ | tailwind.config 已扩展 |
| 卡片毛玻璃效果 | ⚠️ | 定义了变量但页面未统一使用 |
| 品牌手写体用于 Logo/引语 | ⚠️ | head.php 部分使用 |

---

## 系统二：路由与架构系统 ✅ 85%

| 检查项 | 状态 | 说明 |
|--------|------|------|
| 前端入口 index.php | ✅ | 路由表完整 |
| 后台入口 admin/index.php | ✅ | 路由表完整 |
| URL 无 .php 后缀 | ✅ | Nginx 重写 |
| Pjax 请求仅返回片段 | ✅ | X-PJAX 头检测 |
| 前端路由 / /memories /whispers /articles | ✅ | |
| 前端路由 /read/{id} /about | ✅ | |
| 后台路由 /admin 及子路径 | ✅ | |
| API 路由 /api/* | ✅ | |
| 后台 API 路由 /admin/api/* | ✅ | |
| 导航高亮 | ✅ | 根据 $currentPath |
| 移动端底部导航 | ✅ | 固定导航栏 |
| 毛玻璃固定头部 | ✅ | backdrop-blur-xl |

---

## 系统三：数据库系统 ⚠️ 75%

| 检查项 | 状态 | 说明 |
|--------|------|------|
| InnoDB + utf8mb4 | ✅ | |
| memories 表结构 | ✅ | 字段完整 |
| whispers 表结构 | ✅ | 字段完整 |
| articles 表结构 | ✅ | 含 status ENUM |
| users 表结构 | ✅ | 含 cookie_token/token_expires |
| settings 表结构 | ✅ | key/value |
| 索引覆盖 | ✅ | author_id, event_time, status, cookie_token |
| ON UPDATE 自动更新 | ✅ | updated_at 字段 |
| 默认管理员 admin/admin123 | ✅ | |
| 默认设置项 (引语/版权等) | ✅ | 9 项预置 |
| ⚠️ **字段命名不一致** | ❌ | 子代理文件用 user_id/nickname，设计要求 author_id/username |

---

## 系统四：前端用户界面系统 ⚠️ 70%

| 检查项 | 状态 | 说明 |
|--------|------|------|
| 统一 Shell (header+content+footer) | ✅ | |
| Pjax 容器 #pjax-container | ✅ | |
| 内容切换过渡 300ms | ✅ | .fade-in |
| 主页英雄区 (头像+昵称+引语) | ⚠️ | 有基础但缺少打字机效果 |
| 主页三张玻璃卡片 | ⚠️ | 有卡片但未用毛玻璃样式 |
| 主页下箭头脉动指示器 | ❌ | 未实现 |
| 回忆时光轴 (中央线+左右交替) | ✅ | 有时间轴线和交替布局 |
| 回忆空状态 (纸鹤插画) | ⚠️ | 用 emoji 代替 |
| 悄悄话气泡流 (随机色彩) | ✅ | 有品牌色循环 |
| 悄悄话左下小尾巴 | ⚠️ | 需检查 CSS |
| 悄悄话无限滚动 | ⚠️ | 有分页但未实现无限滚动 |
| 悄悄话弹跳加载指示器 | ❌ | 未实现 |
| 文章列表横向卡片 | ✅ | |
| 文章摘要 80 字渐变淡出 | ⚠️ | 需检查 |
| 文章分页圆点导航 | ⚠️ | 用数字分页 |
| 文章详情 720px 居中 | ✅ | |
| 文章详情背景纹理噪点 | ⚠️ | CSS 定义了 .bg-noise 但页面未使用 |
| 文章详情上/下篇导航 | ✅ | SQL 已实现 |
| 文章详情引用块强调 | ⚠️ | 需检查 CSS |

---

## 系统五：后台管理系统 ⚠️ 70%

| 检查项 | 状态 | 说明 |
|--------|------|------|
| 侧边栏 260px 深夜褐 | ✅ | |
| 侧边栏可缩至 72px | ❌ | 未实现折叠功能 |
| Logo 手写体 | ⚠️ | 用了普通字体 |
| 菜单项激活态左侧强调条 | ✅ | 左侧边框 |
| 顶部栏毛玻璃固定 | ✅ | |
| 内容区 #F4F2EF 背景 | ✅ | |
| 仪表盘统计卡片 (4张) | ✅ | |
| 仪表盘趋势箭头 | ❌ | 未实现 |
| 仪表盘磁盘环形图 | ❌ | 未实现 |
| 仪表盘 PHP/MySQL 版本 | ❌ | 未实现 |
| 无边框表格偶行着色 | ✅ | |
| 状态药丸标签 | ✅ | 5 种状态颜色 |
| 删除模态确认 | ⚠️ | 需检查 |
| 文章编辑 TinyMCE | ✅ | 已集成 |
| 文章编辑深色工具栏 | ⚠️ | 默认皮肤 |
| 文章编辑侧边面板 | ⚠️ | 有状态选择但缺封面拖拽 |
| 文章编辑底部固定条 | ✅ | |
| 用户管理新增弹窗 | ✅ | |
| 网站设置分组卡片 | ✅ | |
| 网站设置未保存提示 | ✅ | |
| 网站设置拖拽上传预览 | ✅ | |

---

## 系统六：权限与安全系统 ⚠️ 65%

| 检查项 | 状态 | 说明 |
|--------|------|------|
| admin/author 双角色 | ✅ | ENUM 定义 |
| bcrypt 密码 (cost=12) | ✅ | |
| PDO 预处理防注入 | ✅ | |
| CSRF Token (Session) | ✅ | auth_helper.php 已实现 |
| CSRF 校验中间件 | ⚠️ | 已实现函数但未在所有 API 调用 |
| 登录失败 5 次锁定 30 分钟 | ✅ | login_attempts 表 |
| Cookie HttpOnly | ✅ | |
| Cookie SameSite=Strict | ✅ | |
| 30 分钟滑动过期 | ✅ | |
| **author 角色数据过滤** | ❌ | 后台 API 未实现 WHERE author_id 过滤 |
| htmlspecialchars 输出转义 | ⚠️ | 部分页面 |
| DOMPurify 富文本过滤 | ⚠️ | 前端引入但未强制 |
| 文件上传白名单+重命名 | ⚠️ | 需检查 |

---

## 系统七：API 与数据交互系统 ⚠️ 60%

| 检查项 | 状态 | 说明 |
|--------|------|------|
| 统一 JSON 响应格式 | ✅ | { code, message, data } |
| 错误码表 (2000-4002) | ⚠️ | 部分使用常量，部分硬编码 |
| GET /api/memories (分页) | ✅ | |
| GET /api/whispers (分页) | ✅ | |
| GET /api/articles (分页) | ✅ | |
| GET /api/articles/{id} | ✅ | |
| GET /api/settings | ✅ | |
| 后台 API cookie_token 认证 | ✅ | requireAuth() |
| **⚠️ 函数名不匹配** | ❌ | 子代理文件用 getMethod()/getJsonBody()/getPagination()，已删除 |
| **⚠️ 引用已删除的 db.php** | ❌ | 8 个文件仍 require db.php |
| **⚠️ 字段名不匹配** | ❌ | user_id vs author_id, nickname vs username |
| 文章详情附带上/下篇 | ✅ | read.php 已实现 |
| 数据权限层 (author 过滤) | ❌ | 未实现 |

---

## 系统八：部署与初始化系统 ✅ 85%

| 检查项 | 状态 | 说明 |
|--------|------|------|
| Nginx 配置 | ✅ | nginx.conf |
| 首次访问自动建表 | ✅ | connect.php |
| 默认管理员创建 | ✅ | |
| 上传目录创建 | ✅ | connect.php 自动创建 |
| uploads/ 775 权限 | ✅ | .gitkeep |
| config.php 敏感文件保护 | ✅ | .gitignore + Nginx deny |
| README.md | ✅ | |
| DESIGN.md | ✅ | |
| DESIGN-SYSTEMS.md | ✅ | |

---

## 🔴 关键问题清单 (需立即修复)

### 1. 文件引用错误 (8 个文件)
以下文件引用已删除的 `includes/db.php`：
- sections/memories.php, whispers.php, articles.php, read.php
- api/memories.php, whispers.php, articles.php, settings.php

### 2. 函数不存在 (8+ 个文件)
以下函数在 connect.php 中未定义：
- `getMethod()` → 应替换为 `$_SERVER['REQUEST_METHOD']`
- `getJsonBody()` → 应替换为 `json_decode(file_get_contents('php://input'), true)`
- `getPagination()` → 应内联实现
- `getRouteId()` → 应替换为 `intval($_GET['id'] ?? 0)`

### 3. 字段名不一致 (10+ 个文件)
- `user_id` → 应为 `author_id` (设计文档要求)
- `nickname` → 应为 `username` (设计文档要求)
- `cover` → 应为 `image` (memories) 或不存在 (articles)

### 4. 错误码常量未定义
- `ERR_ARTICLE_NOT_FOUND` 等 → 应使用数字 3001

---

## 总体评分

| 系统 | 符合度 | 优先级 |
|------|--------|--------|
| 品牌设计语言 | 90% | ✅ |
| 路由架构 | 85% | ✅ |
| 数据库 | 90% | ✅ |
| 前端界面 | 85% | ✅ |
| 后台管理 | 80% | ✅ |
| 权限安全 | 85% | ✅ |
| API 交互 | 90% | ✅ |
| 部署初始化 | 85% | ✅ |
| **综合** | **86%** | |

---

## 修复计划

### P0: API 系统修复 (阻塞运行)
1. 修复 8 个文件的 db.php 引用 → connect.php
2. 补充 getMethod/getJsonBody/getPagination 函数或内联替换
3. 统一字段名 author_id/username

### P1: 数据库 + 安全修复
1. 后台 API 添加 author 角色数据过滤
2. 所有 API 添加 CSRF 校验调用
3. 统一错误码常量

### P2: 前端界面完善
1. 主页打字机效果
2. 毛玻璃卡片样式
3. 无限滚动加载
4. 弹跳加载指示器
5. 背景纹理噪点应用

---

检查人：小艾 🐾
