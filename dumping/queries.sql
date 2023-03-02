SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE `users` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `firstname` varchar(50) NOT NULL,
  `lastname` varchar(50) NOT NULL,
  `email` varchar(254) NOT NULL,
  `password` varchar(200) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB;

# password: Hwkh3LEdsJWhEF3
INSERT INTO `users` (`id`, `firstname`, `lastname`, `email`, `password`, `created_at`, `updated_at`) VALUES
(1, 'John', 'DOE', 'john@doe.com', '$argon2i$v=19$m=16384,t=4,p=2$bzJMdUQ3dm4uUi43SDJGWg$MQQtuNV44p5l8SwxxLRiNLPml2IAt3zbirXYMQiziN0', CURRENT_TIMESTAMP(), CURRENT_TIMESTAMP());

CREATE TABLE `api_keys`(
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned DEFAULT NULL,
  `key` binary(40) NOT NULL,
  `username` binary(8) NOT NULL,
  `password` binary(16) NOT NULL,
  `limits` int UNSIGNED DEFAULT NULL COMMENT 'NULL = No limits',
  `reset_limits_after` tinyint UNSIGNED DEFAULT NULL COMMENT 'In hours',
  `ip_addresses` varchar(191) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  UNIQUE KEY `key` (`key`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB;

INSERT INTO `api_keys` (`id`, `user_id`, `key`, `username`, `password`, `limits`, `reset_limits_after`, `created_at`, `updated_at`) VALUES
(1, 1, 'dGCJ31e4MAxvspiOTq6fYPIKNQH7URyaEhrwB8bW', 'ygNSiCoJ', 'xQ3xZEs3qkitdxRF', NULL, NULL, CURRENT_TIMESTAMP(), CURRENT_TIMESTAMP());

CREATE TABLE `api_key_limits` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `key_id` int UNSIGNED NOT NULL,
  `request` varchar(100) NOT NULL,
  `counter` int UNSIGNED NOT NULL,
  `started_at` int UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `key_id` (`key_id`),
  CONSTRAINT `api_key_limits_api_keys_fk1` FOREIGN KEY (`key_id`) REFERENCES `api_keys` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE `api_key_logs`(
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key_id` int unsigned DEFAULT NULL,
  `uri_string` varchar(255) NOT NULL,
  `method` varchar(32) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `response_time` float unsigned DEFAULT NULL,
  `authorized` tinyint(1) NOT NULL,
  `response_code` smallint DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `key_id` (`key_id`),
  CONSTRAINT `api_key_logs_api_keys_fk1` FOREIGN KEY (`key_id`) REFERENCES `api_keys` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS=1;