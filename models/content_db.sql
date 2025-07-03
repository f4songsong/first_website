CREATE TABLE `content` (
  `content_id` int NOT NULL AUTO_INCREMENT,
  block_type ENUM('text', 'image', 'video', 'link', 'source_code', 'math', 'emoji') NOT NULL,
  `content` text COLLATE utf8mb3_bin NOT NULL,
  `post_id` int NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`post_id`) REFERENCES `post` (`post_id`),
  PRIMARY KEY (`content_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;
