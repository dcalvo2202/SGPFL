<?php
/**
 * Constantes del sistema SGPFL
 * Centraliza valores usados en múltiples partes del código
 */

// =============================== ESTADOS DE PROPUESTAS TFG ===============================
define('TFG_STATUS_PENDING', 'Pendiente de Revision');
define('TFG_STATUS_IN_REVIEW', 'En Revisión');
define('TFG_STATUS_MEETS_REQUIREMENTS', 'Cumple requisitos');
define('TFG_STATUS_APPROVED', 'Aprobado');
define('TFG_STATUS_REJECTED', 'Rechazada');

// Array de estados que bloquean nueva subida de propuesta
define('TFG_BLOCKING_STATUSES', [
    TFG_STATUS_PENDING,
    TFG_STATUS_IN_REVIEW,
    TFG_STATUS_MEETS_REQUIREMENTS,
    TFG_STATUS_APPROVED
]);

// =============================== ESTADOS DE DOCUMENTOS FINALES ===============================
define('DOC_STATUS_PENDING', 'Pendiente de Revision');
define('DOC_STATUS_APPROVED', 'Aprobado');
define('DOC_STATUS_REJECTED', 'Rechazado');

// =============================== ROLES DE USUARIO ===============================
// Basado en dashboard.php y la estructura real del sistema
define('ROL_ADMIN', 1);           // Administrador
define('ROL_GESTOR', 2);          // Gestor Académico (panel_subdireccion)
define('ROL_CTFG', 3);            // Comisión TFG
define('ROL_ESTUDIANTE', 4);      // Estudiante
define('ROL_ASESOR', 5);          // Asesor Externo
// ROL_SUBDIRECCION es alias de ROL_GESTOR para compatibilidad
define('ROL_SUBDIRECCION', ROL_GESTOR);

// =============================== LÍMITES DE ARCHIVOS ===============================
define('MAX_FILE_SIZE_PROPOSAL_MB', 10);
define('MAX_FILE_SIZE_FINAL_MB', 20);
define('MIN_FILE_SIZE_FINAL_KB', 100);

// =============================== TIPOS DE ARCHIVO PERMITIDOS ===============================
define('ALLOWED_TYPES_PROPOSAL', ['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);
define('ALLOWED_TYPES_FINAL', ['application/pdf']);

// =============================== ESTADOS DE PROYECTOS REGISTRADOS ===============================
define('PROJECT_STATUS_DRAFT', 'Borrador');
define('PROJECT_STATUS_REGISTERED', 'Registrado');
define('PROJECT_STATUS_APPROVED', 'Aprobado');
define('PROJECT_STATUS_REJECTED', 'Rechazado');
define('PROJECT_STATUS_ACTIVE', 'Vigente');
define('PROJECT_STATUS_EXTENSION', 'Prórroga Activa');
define('PROJECT_STATUS_CONCLUDED', 'Concluido');
define('PROJECT_STATUS_CANCELLED', 'Cancelado');

// Estados válidos para proyectos
define('PROJECT_VALID_STATUSES', [
    PROJECT_STATUS_DRAFT,
    PROJECT_STATUS_REGISTERED,
    PROJECT_STATUS_APPROVED,
    PROJECT_STATUS_REJECTED,
    PROJECT_STATUS_ACTIVE,
    PROJECT_STATUS_EXTENSION,
    PROJECT_STATUS_CONCLUDED,
    PROJECT_STATUS_CANCELLED
]);

// Estados que disparan archivado automático (HU-027)
define('PROJECT_ARCHIVE_STATUSES', [
    PROJECT_STATUS_CONCLUDED,
    PROJECT_STATUS_CANCELLED
]);

// Alias para compatibilidad con código que usa nombre diferente
define('ROL_ADMINISTRADOR', ROL_ADMIN);
