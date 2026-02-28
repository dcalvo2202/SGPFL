<?php
/**
 * Script de inicialización de tabla de recuperación de contraseñas
 * Se ejecuta automáticamente en la primera carga o cuando sea necesario
 */

function init_password_recovery_table() {
    try {
        $id_con = isset($GLOBALS['id_con']) ? $GLOBALS['id_con'] : null;
        
        if (!$id_con) {
            require_once __DIR__ . '/db.php';
        }

        // SQL para crear la tabla
        $sql = "CREATE TABLE IF NOT EXISTS `password_recovery_tokens` (
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
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='Tokens para recuperación de contraseñas'";

        if (!mysqli_query($id_con, $sql)) {
            // Log silencioso si ya existe
            error_log("Password recovery table already exists or query executed");
        }

        return true;
    } catch (Exception $e) {
        error_log("Error initializing password recovery table: " . $e->getMessage());
        return false;
    }
}

// Ejecutar inicialización si se llama directamente
if (php_sapi_name() !== 'cli' && basename($_SERVER['PHP_SELF']) === 'init_password_recovery.php') {
    if (init_password_recovery_table()) {
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Tabla de recuperación de contraseña inicializada']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al inicializar la tabla']);
    }
}
?>
