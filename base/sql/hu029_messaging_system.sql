-- ============================================================================
-- HU-029: Sistema de Comunicación Interna (Chat tipo Teams)
-- Permite la comunicación entre Estudiante, Comisión y Comité Asesor
-- ============================================================================
-- Ejecutar sobre la base de datos: base_db
-- ============================================================================

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- TABLA 1: CONVERSACIONES (individuales y grupales)
-- ----------------------------
DROP TABLE IF EXISTS `chat_messages`;
DROP TABLE IF EXISTS `chat_participants`;
DROP TABLE IF EXISTS `chat_conversations`;

CREATE TABLE `chat_conversations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `conversation_type` enum('individual','group') NOT NULL DEFAULT 'individual' COMMENT 'Tipo de conversación',
  `project_id` int(11) DEFAULT NULL COMMENT 'FK a proyecto_aprobado para chats de proyecto',
  `group_name` varchar(255) DEFAULT NULL COMMENT 'Nombre del chat grupal (nombre del proyecto)',
  `created_by` varchar(50) NOT NULL COMMENT 'Usuario que creó la conversación',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_project_group` (`project_id`),
  KEY `idx_conversation_type` (`conversation_type`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_updated_at` (`updated_at`),
  CONSTRAINT `fk_chat_conv_project` FOREIGN KEY (`project_id`) REFERENCES `proyecto_aprobado` (`id_aprobado`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_chat_conv_creator` FOREIGN KEY (`created_by`) REFERENCES `sis_user` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='HU-029: Conversaciones del sistema de mensajería';

-- ----------------------------
-- TABLA 2: PARTICIPANTES DE CONVERSACIÓN
-- ----------------------------
CREATE TABLE `chat_participants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `conversation_id` int(11) NOT NULL COMMENT 'FK a chat_conversations',
  `user_id` varchar(50) NOT NULL COMMENT 'FK a sis_user',
  `joined_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `last_read_at` datetime DEFAULT NULL COMMENT 'Última vez que el usuario leyó esta conversación',
  `is_active` tinyint(1) DEFAULT 1 COMMENT '1=activo, 0=abandonó el chat',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_conversation_user` (`conversation_id`, `user_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_last_read` (`last_read_at`),
  CONSTRAINT `fk_chat_part_conversation` FOREIGN KEY (`conversation_id`) REFERENCES `chat_conversations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_chat_part_user` FOREIGN KEY (`user_id`) REFERENCES `sis_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='HU-029: Participantes de cada conversación';

-- ----------------------------
-- TABLA 3: MENSAJES
-- ----------------------------
CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `conversation_id` int(11) NOT NULL COMMENT 'FK a chat_conversations',
  `sender_id` varchar(50) NOT NULL COMMENT 'FK a sis_user (quien envió el mensaje)',
  `message_text` text NOT NULL COMMENT 'Contenido del mensaje (solo texto)',
  `sent_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `edited_at` datetime DEFAULT NULL COMMENT 'Fecha de edición (si se editó)',
  `is_deleted` tinyint(1) DEFAULT 0 COMMENT 'Soft delete: 1=eliminado',
  PRIMARY KEY (`id`),
  KEY `idx_conversation_sent` (`conversation_id`, `sent_at`),
  KEY `idx_sender_id` (`sender_id`),
  KEY `idx_sent_at` (`sent_at`),
  KEY `idx_is_deleted` (`is_deleted`),
  CONSTRAINT `fk_chat_msg_conversation` FOREIGN KEY (`conversation_id`) REFERENCES `chat_conversations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_chat_msg_sender` FOREIGN KEY (`sender_id`) REFERENCES `sis_user` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='HU-029: Mensajes del sistema de chat';

SET FOREIGN_KEY_CHECKS=1;

-- ============================================================================
-- FIN HU-029
-- ============================================================================
