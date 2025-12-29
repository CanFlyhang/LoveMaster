-- 数据库初始化脚本

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. 用户表 (Users)
-- 包含微信 OpenID (模拟) 和性别
CREATE TABLE IF NOT EXISTS `users` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `openid` VARCHAR(64) NOT NULL COMMENT '用户唯一标识',
  `nickname` VARCHAR(64) DEFAULT '' COMMENT '昵称',
  `gender` TINYINT DEFAULT 0 COMMENT '1:男, 2:女, 0:未知',
  `credits` INT DEFAULT 10 COMMENT '剩余问答次数',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_openid` (`openid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户基础表';

-- 2. 游戏会话表 (GameSessions)
-- 记录一次完整的游戏过程
CREATE TABLE IF NOT EXISTS `game_sessions` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `scenario_type` VARCHAR(20) NOT NULL COMMENT 'dating_male, dating_female, workplace',
  `total_score` INT DEFAULT 0 COMMENT '当前总分',
  `round_count` INT DEFAULT 0 COMMENT '当前轮数',
  `is_active` TINYINT DEFAULT 1 COMMENT '1:进行中, 0:已结束',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_user_active` (`user_id`, `is_active`),
  CONSTRAINT `fk_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='游戏会话主表';

-- 3. 对话日志表 (ChatLogs)
-- 记录每一轮的问答和评分
CREATE TABLE IF NOT EXISTS `chat_logs` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `session_id` BIGINT UNSIGNED NOT NULL,
  `round_index` INT NOT NULL COMMENT '第几轮',
  `npc_message` TEXT COMMENT 'NPC 的提问',
  `user_message` TEXT COMMENT '用户的回答',
  `score` INT COMMENT '本轮得分',
  `analysis` TEXT COMMENT 'AI 的评价与建议',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_session` (`session_id`),
  INDEX `idx_created_at` (`created_at`),
  CONSTRAINT `fk_logs_session` FOREIGN KEY (`session_id`) REFERENCES `game_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='对话流水详表';

-- 4. 排行榜记录表 (Leaderboards)
-- 存储用户的最高分记录
CREATE TABLE IF NOT EXISTS `leaderboards` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `category` VARCHAR(32) NOT NULL COMMENT 'dating_male, dating_female, workplace',
  `score` INT NOT NULL DEFAULT 0,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_user_category` (`user_id`, `category`),
  INDEX `idx_category_score` (`category`, `score` DESC),
  CONSTRAINT `fk_leaderboards_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='排行榜';

SET FOREIGN_KEY_CHECKS = 1;
