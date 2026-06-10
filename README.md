# 🌅 暮想 Museve

> 在薄暮时分，温柔地想起。

**暮想**是一处收藏时光碎片的私密空间——回忆时光轴、悄悄话气泡流、文章静谧阅读，每个像素都在轻声说：你的回忆，值得被温柔珍藏。

## ✨ 特性

- 🕰️ **回忆时光轴** — 左右交替卡片，滚动淡入动画
- 💬 **悄悄话气泡流** — 四色柔和气泡，无限滚动加载
- 📝 **文章系统** — TinyMCE 编辑器，720px 沉浸式阅读
- 🔐 **权限管理** — admin/author 双角色，CSRF 防护，登录锁定
- 🎨 **情感化设计** — 毛玻璃质感、打字机引语、品牌暮光配色
- 📱 **响应式** — 移动端底部导航，桌面端时间轴布局
- ⚡ **Pjax 无刷新** — 页面切换零等待

## 🛠 技术栈

| 层级 | 选型 |
|------|------|
| 后端 | PHP 8.0+ / MySQL 8.0+ |
| 前端 | Tailwind CSS / Alpine.js / Pjax |
| 编辑器 | TinyMCE |
| 图标 | Phosphor Icons |
| 字体 | Noto Serif SC / Inter / ZCOOL XiaoWei |

## 🚀 快速开始

```bash
# 1. 克隆项目
git clone https://github.com/Grabrun/Museve.git

# 2. 配置数据库
cp includes/config.php.example includes/config.php
# 编辑 config.php 填写数据库信息

# 3. 设置 Nginx
# 参考 nginx.conf 配置重写规则

# 4. 访问首页自动建表，然后登录后台
# /admin  (admin / admin123)
```

## 📁 目录结构

```
Museve/
├── index.php              # 前端统一入口 (Pjax 路由)
├── admin/
│   ├── index.php          # 后台统一入口
│   ├── login.php          # 登录页
│   ├── sections/          # 后台页面 (7 个)
│   └── api/               # 后台 API (7 个)
├── sections/              # 前端页面 (7 个)
├── api/                   # 前端 API (4 个)
├── includes/              # 公共组件
├── resources/
│   ├── css/               # 样式文件
│   ├── js/                # 脚本文件
│   └── images/            # 品牌资源
├── uploads/               # 上传文件
├── docs/                  # 设计文档
├── nginx.conf             # Nginx 配置模板
└── README.md
```

## 🎨 品牌色彩

| 色名 | 色值 | 用途 |
|------|------|------|
| 暮光白 | `#F9F7F4` | 主背景 |
| 薄暮玫瑰 | `#DDB8B8` | 主强调色 |
| 静谧蓝 | `#A8C5DA` | 辅助强调 |
| 深夜褐 | `#3E3640` | 主文字 |
| 岁月灰 | `#8E827F` | 辅助文字 |
| 雾绿 | `#87A878` | 已发布 |
| 暖橙 | `#E0A96D` | 待审核 |

## 📖 设计文档

- [V5.2 完整设计](docs/DESIGN.md)
- [八大系统拆分](docs/DESIGN-SYSTEMS.md)
- [设计符合度报告](docs/COMPLIANCE-REPORT.md) (93%)

## 📜 License

MIT

---

> 薄暮玫瑰色的时光容器，每一个像素都在轻声说：你的回忆，值得被温柔珍藏。
