SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE `client` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `key` binary(40) DEFAULT NULL,
  `username` binary(11) NOT NULL,
  `password` binary(21) NOT NULL,
  `limits` int UNSIGNED DEFAULT NULL COMMENT 'Total requests made. NULL = 1000 requests',
  `reset_limits_after` tinyint UNSIGNED DEFAULT NULL COMMENT 'In minutes',
  `ip_addresses` varchar(254) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB;

INSERT INTO `client` (`id`, `key`, `username`, `password`, `limits`, `reset_limits_after`, `created_at`, `updated_at`) VALUES
(1, 'dGCJ31e4MAxvspiOTq6fYPIKNQH7URyaEhrwB8bW', 'ygNSiCoJ', 'xQ3xZEs3qkitdxRF', NULL, NULL, CURRENT_TIMESTAMP(), CURRENT_TIMESTAMP());

CREATE TABLE `client_limit` (
  `id` int UNSIGNED NOT NULL,
  `client_id` int UNSIGNED NOT NULL,
  `request` varchar(100) NOT NULL,
  `counter` int UNSIGNED NOT NULL,
  `started_at` int UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `client_request_unique` (`client_id`,`request`),
  KEY `client_id` (`client_id`),
  CONSTRAINT `client_limit_client_fk1` FOREIGN KEY (`client_id`) REFERENCES `client` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE `client_log`(
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `client_id` int unsigned DEFAULT NULL,
  `uri_string` varchar(255) NOT NULL,
  `method` varchar(32) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `response_time` float unsigned DEFAULT NULL,
  `authorized` tinyint(1) NOT NULL,
  `response_code` smallint DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  CONSTRAINT `client_log_client_fk1` FOREIGN KEY (`client_id`) REFERENCES `client` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS=1;