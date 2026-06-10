# 🌅 暮想 Museve

> 在薄暮时分，温柔地想起。

一处收藏时光碎片的私密空间。

## 技术栈

- **后端**: PHP 8.0+ / MySQL 8.0+
- **前端**: Tailwind CSS / Alpine.js / Pjax
- **编辑器**: TinyMCE

## 快速开始

1. 配置 `includes/config.php` 填写数据库信息
2. 设置 Nginx 参考 `DESIGN.md` 中的重写规则
3. 访问首页，系统自动建表
4. 登录 `/admin` (admin / admin123)

## 目录结构

```
museve/
├── index.php           # 前端入口
├── sections/           # 前端页面模块
├── admin/              # 后台管理
│   ├── sections/       # 后台页面
│   └── api/            # 后台 API
├── includes/           # 公共组件
├── api/                # 前端 API
├── resources/          # 静态资源
└── uploads/            # 上传文件
```

## 品牌色彩

| 色名 | 色值 | 用途 |
|------|------|------|
| 暮光白 | #F9F7F4 | 主背景 |
| 薄暮玫瑰 | #DDB8B8 | 主强调色 |
| 深夜褐 | #3E3640 | 主文字 |
| 静谧蓝 | #A8C5DA | 辅助强调 |

---

© 2026 暮想 Museve · 在薄暮时分，温柔地想起。
