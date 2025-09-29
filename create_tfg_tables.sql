-- Script para crear tablas TFG rápidamente
-- Ejecuta esto en phpMyAdmin

SET FOREIGN_KEY_CHECKS = 0;

-- Eliminar tablas existentes si existen
DROP TABLE IF EXISTS project_history;
DROP TABLE IF EXISTS project_members;
DROP TABLE IF EXISTS registered_projects;
DROP TABLE IF EXISTS tfg_proposals;
DROP TABLE IF EXISTS project_types;

-- TABLA 1: TIPOS DE PROYECTO
CREATE TABLE project_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type_name VARCHAR(100) NOT NULL,
    max_members INT NOT NULL DEFAULT 1,
    description TEXT NULL,
    active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- TABLA 2: PROPUESTAS TFG
CREATE TABLE tfg_proposals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
    title VARCHAR(255) NOT NULL,
    disciplines VARCHAR(255) NOT NULL,
    project_description TEXT NOT NULL,
    document LONGBLOB NULL,
    file_name VARCHAR(255) NOT NULL DEFAULT '',
    mime_type VARCHAR(100) NOT NULL DEFAULT '',
    file_size INT NOT NULL DEFAULT 0,
    status ENUM('Pendiente de Revisión', 'Aprobado', 'Rechazado') DEFAULT 'Pendiente de Revisión',
    admin_comments TEXT NULL,
    reviewed_by VARCHAR(50) NULL,
    reviewed_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_title (title),
    INDEX idx_created (created_at),
    CONSTRAINT fk_tfg_proposals_user FOREIGN KEY (user_id) REFERENCES sis_user(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- TABLA 3: PROYECTOS REGISTRADOS
CREATE TABLE registered_projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tfg_proposal_id INT NOT NULL,
    project_type_id INT NOT NULL,
    status ENUM('Registrado', 'En Desarrollo', 'En Revisión', 'Finalizado', 'Aprobado', 'Rechazado') DEFAULT 'Registrado',
    start_date DATE NULL,
    end_date DATE NULL,
    final_grade DECIMAL(3,1) NULL,
    supervisor_id VARCHAR(50) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tfg_proposal (tfg_proposal_id),
    INDEX idx_project_type (project_type_id),
    INDEX idx_status (status),
    INDEX idx_supervisor (supervisor_id),
    UNIQUE KEY unique_tfg_project (tfg_proposal_id),
    CONSTRAINT fk_registered_projects_tfg FOREIGN KEY (tfg_proposal_id) REFERENCES tfg_proposals(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_registered_projects_type FOREIGN KEY (project_type_id) REFERENCES project_types(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- TABLA 4: MIEMBROS DE PROYECTO (SIN user_name)
CREATE TABLE project_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
    role ENUM('Líder', 'Miembro') NOT NULL,
    status ENUM('Activo', 'Inactivo', 'Retirado') DEFAULT 'Activo',
    joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    left_at DATETIME NULL,
    INDEX idx_project_id (project_id),
    INDEX idx_user_id (user_id),
    INDEX idx_role (role),
    INDEX idx_status (status),
    UNIQUE KEY unique_project_user (project_id, user_id),
    CONSTRAINT fk_project_members_project FOREIGN KEY (project_id) REFERENCES registered_projects(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_project_members_user FOREIGN KEY (user_id) REFERENCES sis_user(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- DATOS INICIALES: TIPOS DE PROYECTO
INSERT INTO project_types (type_name, max_members, description) VALUES
('Desarrollo de Software', 3, 'Proyectos orientados al desarrollo de aplicaciones web, móviles o de escritorio'),
('Investigación', 2, 'Proyectos de investigación teórica o aplicada en ciencias de la computación'),
('Análisis de Sistemas', 2, 'Proyectos de análisis y diseño de sistemas de información'),
('Base de Datos', 2, 'Proyectos relacionados con diseño, optimización y gestión de bases de datos'),
('Redes y Seguridad', 2, 'Proyectos de seguridad informática, redes y sistemas distribuidos');

SET FOREIGN_KEY_CHECKS = 1;

-- Verificar que todo se creó correctamente
SHOW TABLES LIKE '%tfg%';
SHOW TABLES LIKE '%project%';