CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `role_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','error') DEFAULT 'info',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `link` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `role_id` (`role_id`),
  KEY `is_read` (`is_read`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add sample notification
INSERT INTO `notifications` (`user_id`, `role_id`, `title`, `message`, `type`, `is_read`, `link`, `created_at`) VALUES
(NULL, 1, 'Welcome to the Admin Panel', 'You have successfully logged in to the admin panel.', 'success', 0, 'dashboard.php', NOW() - INTERVAL 2 HOUR),
(NULL, 1, 'System Update', 'A new system update is available. Please update at your earliest convenience.', 'info', 0, 'settings.php', NOW() - INTERVAL 1 DAY),
(NULL, 1, 'New User Registration', 'A new user has registered on the system.', 'info', 0, 'users.php', NOW() - INTERVAL 3 DAY);
