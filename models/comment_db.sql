CREATE TABLE `comment` (
  `comment_id` int NOT NULL AUTO_INCREMENT,
  `content` varchar(255) COLLATE utf8mb3_bin NOT NULL,
  `level` int NOT NULL DEFAULT '0',
  `users_id` varchar(20) COLLATE utf8mb3_bin NOT NULL,
  `post_id` int NOT NULL,
  `content_id` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  FOREIGN KEY (`users_id`) REFERENCES `users` (`users_id`),
  FOREIGN KEY (`post_id`) REFERENCES `post` (`post_id`),
  FOREIGN KEY (`content_id`) REFERENCES `content` (`content_id`),
  PRIMARY KEY (`comment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;
