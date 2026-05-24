<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../config.php';

auth_require_role(1);

$sql = "CREATE TABLE IF NOT EXISTS `skill_uploads` (
  `id` int NOT NULL AUTO_INCREMENT,
  `skill` varchar(50) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `mime` varchar(100) DEFAULT NULL,
  `size` int DEFAULT NULL,
  `uploaded_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($conn->query($sql) === TRUE) {
    echo "Migration completed: table skill_uploads exists.";
} else {
    echo "Migration error: " . $conn->error;
}
