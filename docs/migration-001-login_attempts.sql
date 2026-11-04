-- ============================================================
-- 登录尝试记录表
-- ------------------------------------------------------------
-- 说明：应用启动时会自动建表与补索引（见 includes/connect.php 的
--       TABLE_DEFINITIONS），正常部署不需要手动执行本文件。
--       仅在"数据库账号没有建表权限、需要 DBA 预先建好"的场景使用。
--
-- 注意：三个索引缺一不可——
--   idx_ip_time        单 IP 失败次数统计
--   idx_account_time   单账号失败次数统计（IP+账号双键锁定要用）
--   idx_attempted_at   过期记录清理
-- ============================================================

CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `ip` VARCHAR(45) NOT NULL,
    `account` VARCHAR(50) NOT NULL,
    `attempted_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ip_time` (`ip`, `attempted_at`),
    INDEX `idx_account_time` (`account`, `attempted_at`),
    INDEX `idx_attempted_at` (`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
