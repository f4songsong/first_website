CREATE TABLE `users` (
  `users_id` varchar(20) COLLATE utf8mb3_bin NOT NULL,
  `users_name` varchar(20) COLLATE utf8mb3_bin NOT NULL,
  `users_password` varchar(225) COLLATE utf8mb3_bin NOT NULL,
  PRIMARY KEY (`users_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;
