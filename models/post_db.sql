CREATE TABLE `post` (
  `post_id` int NOT NULL,
  `post_title` varchar(45) COLLATE utf8mb3_bin NOT NULL,
  `users_id` varchar(20) COLLATE utf8mb3_bin DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `deletde_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`post_id`),
  KEY `users_id` (`users_id`),
  CONSTRAINT `post_ibfk_1` FOREIGN KEY (`users_id`) REFERENCES `users` (`users_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;
