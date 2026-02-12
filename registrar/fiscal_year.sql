-- Fiscal years table for eguro application
-- Run this in your MySQL/MariaDB database (e.g., via phpMyAdmin or mysql CLI)

CREATE TABLE IF NOT EXISTS `fiscal_years` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label` varchar(100) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `u_label` (`label`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Example inserts (commented out - adjust labels if needed to avoid duplicates with existing data)
-- INSERT INTO `fiscal_years` (`label`, `start_date`, `end_date`, `is_active`) VALUES
-- ('2024-2025', '2024-06-01', '2025-05-31', 0),
-- ('2025-2026', '2025-06-01', '2026-05-31', 1);
