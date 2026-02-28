-- Tabla para almacenar tokens de recuperación de contraseña
-- Este script debe ejecutarse una sola vez para crear la tabla

CREATE TABLE IF NOT EXISTS `password_recovery_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(50) NOT NULL COMMENT 'ID del usuario (cédula)',
  `email` varchar(100) NOT NULL,
  `token` varchar(255) NOT NULL COMMENT 'Token único de recuperación',
  `type` varchar(50) NOT NULL COMMENT 'external_advisor, student, etc.',
  `used` tinyint(1) DEFAULT 0 COMMENT '1 = ya fue usado',
  `used_at` datetime DEFAULT NULL COMMENT 'Fecha/hora de uso',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de creación del token',
  `expires_at` datetime NOT NULL COMMENT 'Fecha/hora de expiración',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_token` (`token`),
  KEY `idx_user_type` (`user_id`, `type`),
  KEY `idx_expires` (`expires_at`),
  KEY `idx_token` (`token`),
  KEY `idx_used` (`used`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='Tokens para recuperación de contraseñas';

-- Índices para limpiar tokens expirados eficientemente
CREATE EVENT IF NOT EXISTS cleanup_expired_tokens
ON SCHEDULE EVERY 1 HOUR
DO
  DELETE FROM password_recovery_tokens 
  WHERE (expires_at < NOW() OR (used = 1 AND used_at < DATE_SUB(NOW(), INTERVAL 7 DAY)))
  LIMIT 1000;
