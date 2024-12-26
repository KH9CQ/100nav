SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`),
  CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `url` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `level` int(11) DEFAULT 1,
  `is_private` tinyint(1) DEFAULT 0,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `links_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `search_engines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `url` varchar(255) NOT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `is_visible` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(50) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 插入默认设置
INSERT IGNORE INTO `settings` (`key`, `value`) 
VALUES 
('site_title', '100导航'),
('site_description', '100导航网站系统'),
('site_keywords', '导航网站系统,网址导航,链接'),
('site_author', 'Admin'),
('site_version', '1.0.0'),
('site_icp', ''),
('site_analytics', ''),
('site_footer', ''),
('allow_register', '0');

-- 插入默认搜索引擎
INSERT INTO `search_engines` (`name`, `url`, `is_default`, `is_visible`, `sort_order`) VALUES 
('Google', 'https://www.google.com/search?q={query}', 1, 1, 1),
('百度', 'https://www.baidu.com/s?wd={query}', 0, 1, 2),
('抖音', 'https://www.douyin.com/search/{query}', 0, 1, 3),
('Bilibili', 'https://search.bilibili.com/all?keyword={query}', 0, 1, 4),
('淘宝', 'https://s.taobao.com/search?q={query}', 0, 1, 5),
('1688', 'https://s.1688.com/selloffer/offer_search.htm?keywords={query}', 0, 1, 6),
('搜狗', 'https://www.sogou.com/web?query={query}', 0, 1, 7),
('360', 'https://www.so.com/s?q={query}', 0, 1, 8);

-- 插入默认分类
INSERT INTO `categories` (`name`, `sort_order`) VALUES
('常用链接', 1),
('工具网站', 2);