SET FOREIGN_KEY_CHECKS=0;

# Optional Table
# Used Database session Driver
CREATE TABLE `app_sessions` (
  `id` varchar(128) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `timestamp` int UNSIGNED NOT NULL DEFAULT '0',
  `data` blob NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ci_sessions_timestamp` (`timestamp`)
) ENGINE=InnoDB;

CREATE TABLE `roles` (
  `id` tinyint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL,
  `description` varchar(64) NOT NULL,
  `dashboard` varchar(32) NOT NULL,
  `activation_method` enum('none','email','sms') NOT NULL DEFAULT 'none',
  `manual_activation` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

INSERT INTO `roles` (`id`, `name`, `description`, `dashboard`, `activation_method`, `manual_activation`, `created_at`, `updated_at`) VALUES
(1, 'moderator', 'Moderator', 'dashboard', 'none', 0, CURRENT_TIMESTAMP(), CURRENT_TIMESTAMP()),
(2, 'admin', 'Administrator', 'dashboard', 'none', 0, CURRENT_TIMESTAMP(), CURRENT_TIMESTAMP()),
(3, 'super', 'Super administrator', 'dashboard', 'none', 0, CURRENT_TIMESTAMP(), CURRENT_TIMESTAMP()),
(4, 'member', 'Member', 'profile', 'email', 0, CURRENT_TIMESTAMP(), CURRENT_TIMESTAMP());

CREATE TABLE `users` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `gender_id` tinyint UNSIGNED NOT NULL DEFAULT '2',
  `firstname` varchar(50) NOT NULL,
  `lastname` varchar(50) NOT NULL,
  `email` varchar(254) NOT NULL,
  `password` varchar(200) NOT NULL,
  `phone` varchar(13) DEFAULT NULL,
  `country_iso` varchar(2) DEFAULT NULL,
  `hometown` varchar(50) DEFAULT NULL,
  `position_held` varchar(64) DEFAULT NULL,
  `activation_selector` varchar(255) DEFAULT NULL,
  `activation_code` varchar(255) DEFAULT NULL,
  `forgotten_password_selector` varchar(255) DEFAULT NULL,
  `forgotten_password_code` varchar(255) DEFAULT NULL,
  `forgotten_password_time` int UNSIGNED DEFAULT NULL,
  `remember_selector` varchar(255) DEFAULT NULL,
  `remember_code` varchar(255) DEFAULT NULL,
  `active` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `ip_address` varchar(45) NOT NULL,
  `last_login` int UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `activation_selector` (`activation_selector`),
  UNIQUE KEY `forgotten_password_selector` (`forgotten_password_selector`),
  UNIQUE KEY `remember_selector` (`remember_selector`)
) ENGINE=InnoDB;

# password: Hwkh3LEdsJWhEF3
INSERT INTO `users` (`id`, `gender_id`, `firstname`, `lastname`, `email`, `password`, `phone`, `country_iso`, `hometown`, `position_held`, `activation_selector`, `activation_code`, `forgotten_password_selector`, `forgotten_password_code`, `forgotten_password_time`, `remember_selector`, `remember_code`, `active`, `ip_address`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 1, 'John', 'DOE', 'john@doe.com', '$argon2i$v=19$m=16384,t=4,p=2$bzJMdUQ3dm4uUi43SDJGWg$MQQtuNV44p5l8SwxxLRiNLPml2IAt3zbirXYMQiziN0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '::1', NULL, CURRENT_TIMESTAMP(), CURRENT_TIMESTAMP());

CREATE TABLE `users_roles` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `role_id` tinyint UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ug_users_roles` (`user_id`,`role_id`),
  KEY `user_id` (`user_id`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `users_roles_roles_fk1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `users_roles_users_fk1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO `users_roles` (`id`, `user_id`, `role_id`, `created_at`, `updated_at`) VALUES
(1, 1, 3, CURRENT_TIMESTAMP(), CURRENT_TIMESTAMP());

CREATE TABLE `api_keys`(
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `key` binary(40) NOT NULL,
  `ip_addresses` varchar(191) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  UNIQUE KEY `key` (`key`),
  CONSTRAINT `api_keys_users_fk1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
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