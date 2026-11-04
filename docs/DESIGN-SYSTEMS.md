# 暮想 Museve · 设计文档系统拆分

V5.3 完整设计方案拆分为八个核心系统，确保各团队可并行理解与实施。

## 系统一：品牌与设计语言系统
- 品牌色板 11 色，主强调 #DDB8B8，背景 #F9F7F4
- 设计 Token: --bg-primary, --card-bg, --accent, --radius-sm/md/lg, --shadow-card/hover, --text-primary/secondary
- 字体: Noto Serif SC (正文), Inter (UI), ZCOOL XiaoWei/Caveat (品牌手写)
- 图标: Phosphor Icons (线形 1.5px 描边)

## 系统二：路由与架构系统
- 前端入口 index.php, 后台 admin/index.php
- URL 无 .php 后缀
- Pjax 请求仅返回内容片段
- API 统一 JSON 响应

## 系统三：数据库系统
- InnoDB, utf8mb4_unicode_ci
- 8 张表: memories, milestones, whispers, articles, users, settings, logs, login_attempts
- 运行时自建表：启动时探测缺失表并补齐，覆盖式部署到旧库也能自动迁移
- 默认管理员 admin/admin123（**仅首次建库时创建，上线后必须改密**）

## 系统四：前端用户界面系统
- 统一 Shell: header + content + footer
- 导航带底部滑动指示条
- 移动端底部固定导航栏
- 主页英雄区 + 玻璃卡片组
- 回忆时光轴(左右交替)
- 悄悄话气泡流(无限滚动)
- 文章列表(图文卡片)
- 文章详情(720px 居中, 上/下篇导航)

## 系统五：后台管理系统
- 侧边栏 260px(可缩至 72px)
- 仪表盘(统计卡片 + 磁盘环形图 + 版本信息)
- 内容管理(无边框表格 + 状态药丸)
- 文章编辑(TinyMCE 深色工具栏 + 侧边面板)
- 用户管理(模态弹窗)
- 网站设置(分组卡片 + 拖拽上传)

## 系统六：权限与安全系统
- admin/author 双角色，API 与页面层各自门禁
- CSRF Token (Session，登录成功时重新生成)
- 登录保护: IP + 账号双键计数，5 次失败锁定 30 分钟（避免攻击者刷错密码锁死站长）
- Cookie: HttpOnly, Secure(跟随协议), SameSite=Strict；登录后 session_regenerate_id
- author 角色自动附加 WHERE author_id
- 最后一位管理员不可降级或删除，防止把自己锁在后台外面
- 富文本入库前经服务端白名单净化（标签/属性/协议三级）

## 系统七：API 与数据交互系统
- 统一 JSON 响应 { code, message, data }
- 前端公开 API 4 个（只读）: articles, memories, whispers, settings
- 后台 API 8 个: articles, auth, memories, milestones, settings, upload, users, whispers
- 数据权限层: author 只看自己的数据

## 系统八：部署与初始化系统
- PHP 8.1+, MySQL 8.0+, Nginx 1.18+
- 首次访问自动建表；缺 `config.php` 时给出安装提示而非致命错误
- 目录权限: wwwroot/ 755, uploads/ 775, config.php 600
- 前端依赖全部本地化，构建产物随仓库提交，部署机不需要 Node
- `npm run pack` 可产出只含运行期文件的部署包

---

版本：V5.3 · 暮想 Museve 标准化重构版
最后更新：2026-11-05
