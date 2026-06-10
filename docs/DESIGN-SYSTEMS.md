# 暮想 Museve · 设计文档系统拆分

V5.2 完整设计方案拆分为八个核心系统，确保各团队可并行理解与实施。

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
- 5 张表: memories, whispers, articles, users, settings
- 默认管理员 admin/admin123

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
- admin/author 双角色
- CSRF Token (Session, 每次登录刷新)
- 登录保护: 5 次失败锁定 30 分钟
- Cookie: HttpOnly, Secure, SameSite=Strict
- author 角色自动附加 WHERE author_id

## 系统七：API 与数据交互系统
- 统一 JSON 响应 { code, message, data }
- 前端公开 API 5 个
- 后台 CRUD API 7 个
- 数据权限层: author 只看自己的数据

## 系统八：部署与初始化系统
- PHP 8.0+, MySQL 8.0+, Nginx 1.18+
- 首次访问自动建表
- 目录权限: wwwroot/ 755, uploads/ 775, config.php 600

---

版本：V5.2 · 暮想 Museve 标准化重构版
最后更新：2026-06-10
