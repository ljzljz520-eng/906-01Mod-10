-- 创建数据库
CREATE DATABASE IF NOT EXISTS torrent_search CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 授权给 baobei 用户
GRANT ALL PRIVILEGES ON torrent_search.* TO 'baobei'@'%';
FLUSH PRIVILEGES;

USE torrent_search;

-- 创建用户表
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE COMMENT '用户名',
  email VARCHAR(100) NOT NULL UNIQUE COMMENT '邮箱',
  password_hash VARCHAR(255) NOT NULL COMMENT '密码哈希',
  role ENUM('guest', 'employee', 'admin') NOT NULL DEFAULT 'guest' COMMENT '角色：访客/员工/管理员',
  status ENUM('active', 'inactive', 'resigned') NOT NULL DEFAULT 'active' COMMENT '状态：在职/禁用/已离职',
  real_name VARCHAR(50) COMMENT '真实姓名',
  department VARCHAR(100) COMMENT '部门',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  INDEX idx_role (role),
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户表';

-- 创建IP白名单表
CREATE TABLE IF NOT EXISTS ip_whitelist (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ip_start VARCHAR(45) NOT NULL COMMENT '起始IP',
  ip_end VARCHAR(45) NOT NULL COMMENT '结束IP',
  description VARCHAR(255) COMMENT '描述',
  created_by INT COMMENT '创建人ID',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  INDEX idx_ip_range (ip_start, ip_end)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='IP白名单表';

-- 创建内网资源表
CREATE TABLE IF NOT EXISTS intranet_resources (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL COMMENT '资源名称',
  resource_type ENUM('component', 'design', 'deploy') NOT NULL COMMENT '资源类型：组件仓库/设计规范/部署脚本',
  version VARCHAR(50) NOT NULL COMMENT '版本号',
  description TEXT COMMENT '资源描述',
  url VARCHAR(500) COMMENT '资源地址',
  maintainer_id INT NOT NULL COMMENT '负责人ID',
  expire_date DATE COMMENT '失效日期',
  is_active TINYINT(1) DEFAULT 1 COMMENT '是否启用',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  INDEX idx_type (resource_type),
  INDEX idx_maintainer (maintainer_id),
  INDEX idx_expire (expire_date),
  INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内网资源表';

-- 创建搜索历史表
CREATE TABLE IF NOT EXISTS search_history (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT DEFAULT NULL COMMENT '用户ID（访客为NULL）',
  keyword VARCHAR(50) NOT NULL COMMENT '搜索源',
  query VARCHAR(255) NOT NULL COMMENT '搜索关键词',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  INDEX idx_user_id (user_id),
  INDEX idx_created_at (created_at),
  INDEX idx_keyword (keyword)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='搜索历史表';

-- 创建收藏表
CREATE TABLE IF NOT EXISTS favorites (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL COMMENT '用户ID',
  resource_type ENUM('public', 'intranet') NOT NULL DEFAULT 'public' COMMENT '资源类型：公网/内网',
  intranet_resource_id INT DEFAULT NULL COMMENT '内网资源ID',
  name VARCHAR(500) NOT NULL COMMENT '资源名称',
  magnet TEXT COMMENT '磁力链接（公网资源）',
  size VARCHAR(50) COMMENT '文件大小',
  seeders INT DEFAULT 0 COMMENT '做种数',
  leechers INT DEFAULT 0 COMMENT '下载数',
  category VARCHAR(100) COMMENT '分类',
  source VARCHAR(50) COMMENT '来源站点',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  INDEX idx_user_id (user_id),
  INDEX idx_resource_type (resource_type),
  INDEX idx_intranet_resource (intranet_resource_id),
  INDEX idx_created_at (created_at),
  INDEX idx_source (source)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='收藏表';

-- 插入示例用户数据
INSERT INTO users (username, email, password_hash, role, status, real_name, department) VALUES
('admin', 'admin@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active', '系统管理员', '技术部'),
('zhangsan', 'zhangsan@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', 'active', '张三', '前端开发组'),
('lisi', 'lisi@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', 'active', '李四', '后端开发组'),
('wangwu', 'wangwu@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', 'resigned', '王五', '设计组');

-- 插入示例IP白名单数据
INSERT INTO ip_whitelist (ip_start, ip_end, description, created_by) VALUES
('192.168.0.0', '192.168.255.255', '公司内网A段', 1),
('10.0.0.0', '10.255.255.255', '公司内网B段', 1),
('172.16.0.0', '172.31.255.255', '公司内网C段', 1),
('127.0.0.1', '127.0.0.1', '本地回环地址', 1);

-- 插入示例内网资源数据
INSERT INTO intranet_resources (name, resource_type, version, description, url, maintainer_id, expire_date, is_active) VALUES
('企业UI组件库', 'component', 'v2.3.1', '公司统一的Vue组件库，包含按钮、表单、表格等常用组件', 'http://git.company.com/fe/ui-components', 2, '2027-12-31', 1),
('接口请求SDK', 'component', 'v1.5.0', '统一的HTTP请求封装，支持拦截器、重试、缓存等功能', 'http://git.company.com/fe/request-sdk', 3, '2026-06-30', 1),
('设计规范手册', 'design', 'v3.0.0', '公司品牌设计规范，包含配色、字体、图标等', 'http://design.company.com/spec', 4, '2027-06-01', 1),
('K8s部署脚本', 'deploy', 'v1.2.0', 'Kubernetes集群一键部署脚本集', 'http://git.company.com/devops/k8s-scripts', 3, '2026-12-31', 1),
('微服务脚手架', 'component', 'v2.0.0', 'Spring Boot微服务项目脚手架模板', 'http://git.company.com/be/microservice-starter', 3, NULL, 1),
('旧版UI组件库', 'component', 'v1.8.0', '已废弃的旧版组件库，仅供历史项目维护使用', 'http://git.company.com/fe/legacy-ui', 2, '2025-12-31', 0);

-- 插入示例搜索历史数据
INSERT INTO search_history (user_id, keyword, query, created_at) VALUES
(2, '1337x', 'Avengers', DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(3, 'yts', 'Inception', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(NULL, 'eztv', 'Breaking Bad', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(2, '1337x', 'The Matrix', DATE_SUB(NOW(), INTERVAL 5 DAY));

-- 插入示例收藏数据
INSERT INTO favorites (user_id, resource_type, intranet_resource_id, name, magnet, size, seeders, leechers, category, source, created_at) VALUES
(2, 'public', NULL, 'Avengers: Endgame (2019) [1080p]', 'magnet:?xt=urn:btih:EXAMPLE1234567890ABCDEF&dn=Avengers+Endgame', '2.5 GB', 1250, 85, 'Movies', '1337x', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(3, 'public', NULL, 'The Dark Knight (2008) [720p]', 'magnet:?xt=urn:btih:EXAMPLE0987654321FEDCBA&dn=The+Dark+Knight', '1.8 GB', 890, 42, 'Movies', 'yts', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(2, 'intranet', 1, '企业UI组件库 v2.3.1', NULL, NULL, 0, 0, 'component', 'intranet', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(3, 'intranet', 4, 'K8s部署脚本 v1.2.0', NULL, NULL, 0, 0, 'deploy', 'intranet', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(4, 'intranet', 3, '设计规范手册 v3.0.0', NULL, NULL, 0, 0, 'design', 'intranet', DATE_SUB(NOW(), INTERVAL 10 DAY));

-- 创建系统日志表
CREATE TABLE IF NOT EXISTS system_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  log_type VARCHAR(50) NOT NULL COMMENT '日志类型',
  message TEXT NOT NULL COMMENT '日志内容',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  INDEX idx_log_type (log_type),
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统日志表';

-- 创建用户令牌表（如果不存在）
CREATE TABLE IF NOT EXISTS user_tokens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL COMMENT '用户ID',
  token VARCHAR(64) NOT NULL UNIQUE COMMENT '令牌',
  expires_at DATETIME NOT NULL COMMENT '过期时间',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  INDEX idx_user_id (user_id),
  INDEX idx_token (token),
  INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户令牌表';

-- 添加外键约束（使用存储过程保证 MySQL 兼容性，支持 IF NOT EXISTS 语义）
DELIMITER //

DROP PROCEDURE IF EXISTS add_foreign_key_if_not_exists //
CREATE PROCEDURE add_foreign_key_if_not_exists(
    IN table_name VARCHAR(100),
    IN constraint_name VARCHAR(100),
    IN foreign_key_sql TEXT
)
BEGIN
    DECLARE constraint_exists INT DEFAULT 0;
    
    SELECT COUNT(*) INTO constraint_exists
    FROM information_schema.REFERENTIAL_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND CONSTRAINT_NAME = constraint_name
      AND TABLE_NAME = table_name;
    
    IF constraint_exists = 0 THEN
        SET @sql = foreign_key_sql;
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END //

DROP PROCEDURE IF EXISTS add_index_if_not_exists //
CREATE PROCEDURE add_index_if_not_exists(
    IN table_name VARCHAR(100),
    IN index_name VARCHAR(100),
    IN index_sql TEXT
)
BEGIN
    DECLARE index_exists INT DEFAULT 0;
    
    SELECT COUNT(*) INTO index_exists
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = table_name
      AND INDEX_NAME = index_name;
    
    IF index_exists = 0 THEN
        SET @sql = index_sql;
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END //

DELIMITER ;

-- ip_whitelist 外键
CALL add_foreign_key_if_not_exists(
    'ip_whitelist',
    'fk_ip_whitelist_created_by',
    'ALTER TABLE ip_whitelist ADD CONSTRAINT fk_ip_whitelist_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL'
);

-- intranet_resources 外键
CALL add_foreign_key_if_not_exists(
    'intranet_resources',
    'fk_intranet_resources_maintainer',
    'ALTER TABLE intranet_resources ADD CONSTRAINT fk_intranet_resources_maintainer FOREIGN KEY (maintainer_id) REFERENCES users(id) ON DELETE RESTRICT'
);

-- search_history 外键
CALL add_foreign_key_if_not_exists(
    'search_history',
    'fk_search_history_user',
    'ALTER TABLE search_history ADD CONSTRAINT fk_search_history_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL'
);

-- favorites 外键（用户）
CALL add_foreign_key_if_not_exists(
    'favorites',
    'fk_favorites_user',
    'ALTER TABLE favorites ADD CONSTRAINT fk_favorites_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE'
);

-- favorites 外键（内网资源）
CALL add_foreign_key_if_not_exists(
    'favorites',
    'fk_favorites_intranet_resource',
    'ALTER TABLE favorites ADD CONSTRAINT fk_favorites_intranet_resource FOREIGN KEY (intranet_resource_id) REFERENCES intranet_resources(id) ON DELETE SET NULL'
);

-- user_tokens 外键
CALL add_foreign_key_if_not_exists(
    'user_tokens',
    'fk_user_tokens_user',
    'ALTER TABLE user_tokens ADD CONSTRAINT fk_user_tokens_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE'
);

-- 创建触发器：内网资源删除时自动清理相关收藏
DELIMITER //
DROP TRIGGER IF EXISTS trg_intranet_resources_delete //
CREATE TRIGGER trg_intranet_resources_delete
AFTER DELETE ON intranet_resources
FOR EACH ROW
BEGIN
  DELETE FROM favorites WHERE intranet_resource_id = OLD.id AND resource_type = 'intranet';
  
  INSERT INTO system_logs (log_type, message, created_at) 
  VALUES ('resource_deleted', 
          CONCAT('内网资源被删除: ', OLD.name, ' (', OLD.version, '), 类型: ', OLD.resource_type, 
                 ', 负责人ID: ', OLD.maintainer_id, 
                 ', 相关收藏已自动清理'), 
          NOW());
END //

-- 创建触发器：用户状态变更（离职/禁用）时清理内网收藏
DROP TRIGGER IF EXISTS trg_users_status_update //
CREATE TRIGGER trg_users_status_update
AFTER UPDATE ON users
FOR EACH ROW
BEGIN
  IF OLD.status <> NEW.status THEN
    -- 离职：清理内网收藏和令牌
    IF NEW.status = 'resigned' THEN
      DELETE FROM favorites WHERE user_id = NEW.id AND resource_type = 'intranet';
      DELETE FROM user_tokens WHERE user_id = NEW.id;
      
      INSERT INTO system_logs (log_type, message, created_at) 
      VALUES ('user_status_trigger', 
              CONCAT('触发器: 用户 ', NEW.username, '(ID:', NEW.id, ') 状态变更为离职，内网收藏已清理'), 
              NOW());
      
    -- 禁用：清理所有令牌
    ELSEIF NEW.status = 'inactive' THEN
      DELETE FROM user_tokens WHERE user_id = NEW.id;
      
      INSERT INTO system_logs (log_type, message, created_at) 
      VALUES ('user_status_trigger', 
              CONCAT('触发器: 用户 ', NEW.username, '(ID:', NEW.id, ') 状态变更为禁用，令牌已回收'), 
              NOW());
    END IF;
  END IF;
END //

-- 创建触发器：内网资源变更日志
DROP TRIGGER IF EXISTS trg_intranet_resources_before_update //
CREATE TRIGGER trg_intranet_resources_before_update
BEFORE UPDATE ON intranet_resources
FOR EACH ROW
BEGIN
  -- 记录资源停用日志
  IF OLD.is_active <> NEW.is_active AND NEW.is_active = 0 THEN
    INSERT INTO system_logs (log_type, message, created_at) 
    VALUES ('resource_deactivated', 
            CONCAT('资源被停用: ', NEW.name, ' (', NEW.version, '), 原因: 手动操作或过期'), 
            NOW());
  END IF;
END //

DELIMITER ;

-- 创建复合索引优化查询
CALL add_index_if_not_exists(
    'intranet_resources',
    'idx_intranet_type_active',
    'CREATE INDEX idx_intranet_type_active ON intranet_resources(resource_type, is_active)'
);
CALL add_index_if_not_exists(
    'intranet_resources',
    'idx_intranet_maintainer_active',
    'CREATE INDEX idx_intranet_maintainer_active ON intranet_resources(maintainer_id, is_active)'
);
CALL add_index_if_not_exists(
    'intranet_resources',
    'idx_intranet_expire_active',
    'CREATE INDEX idx_intranet_expire_active ON intranet_resources(expire_date, is_active)'
);
CALL add_index_if_not_exists(
    'favorites',
    'idx_favorites_user_type',
    'CREATE INDEX idx_favorites_user_type ON favorites(user_id, resource_type)'
);
CALL add_index_if_not_exists(
    'favorites',
    'idx_favorites_intranet_user',
    'CREATE INDEX idx_favorites_intranet_user ON favorites(intranet_resource_id, user_id)'
);

-- 清理临时存储过程
DROP PROCEDURE IF EXISTS add_foreign_key_if_not_exists;
DROP PROCEDURE IF EXISTS add_index_if_not_exists;

-- 显示表结构
SHOW TABLES;
SELECT '✅ Database initialized successfully!' AS status;
