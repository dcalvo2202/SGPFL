/*
Navicat MySQL Data Transfer

Source Server         : localhost
Source Server Version : 50719
Source Host           : localhost:3306
Source Database       : base_db

Target Server Type    : MYSQL
Target Server Version : 50719
File Encoding         : 65001

Date: 2026-01-20 12:00:00
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for `sis_log`
-- ----------------------------
DROP TABLE IF EXISTS `sis_log`;
CREATE TABLE `sis_log` (
  `id_bi` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identificador para bitacora',
  `id_user` varchar(50) DEFAULT NULL,
  `date_bi` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `action_type` varchar(50) NOT NULL DEFAULT 'GENERAL' COMMENT 'Tipo de accion auditada',
  `action_result` varchar(20) NOT NULL DEFAULT 'SUCCESS' COMMENT 'Resultado de la accion',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP IPv4/IPv6 del cliente',
  `device_info` varchar(255) DEFAULT NULL COMMENT 'User-Agent o descripcion del dispositivo',
  `detail` text,
  PRIMARY KEY (`id_bi`),
  KEY `idx_sis_log_user_date` (`id_user`,`date_bi`),
  KEY `idx_sis_log_action_result_date` (`action_type`,`action_result`,`date_bi`),
  KEY `idx_sis_log_date` (`date_bi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of sis_log
-- ----------------------------

-- ----------------------------
-- Triggers structure for table `sis_log`
-- ----------------------------
DROP TRIGGER IF EXISTS `trg_sis_log_no_update`;
DELIMITER ;;
CREATE TRIGGER `trg_sis_log_no_update` BEFORE UPDATE ON `sis_log` FOR EACH ROW BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'sis_log es inalterable: no se permite UPDATE';
END
;;
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_sis_log_no_delete`;
DELIMITER ;;
CREATE TRIGGER `trg_sis_log_no_delete` BEFORE DELETE ON `sis_log` FOR EACH ROW BEGIN
  IF IFNULL(@sis_log_allow_purge, 0) <> 1 AND IFNULL(@sis_log_allow_purge_admin, 0) <> 1 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'sis_log es inalterable: no se permite DELETE';
  END IF;
END
;;
DELIMITER ;

-- ----------------------------
-- Table structure for `sis_login`
-- ----------------------------
DROP TABLE IF EXISTS `sis_login`;
CREATE TABLE `sis_login` (
  `id` varchar(50) NOT NULL,
  `pass` varchar(255) NOT NULL,
  `id_roll` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_roll_user` (`id_roll`),
  CONSTRAINT `fk_roll_user` FOREIGN KEY (`id_roll`) REFERENCES `sis_rolls` (`id_roll`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of sis_login
-- ----------------------------

-- Administrador
INSERT INTO `sis_login` VALUES ('205610158', '5d7845ac6ee7cfffafc5fe5f35cf666d', '1');
-- Gestor Academico
INSERT INTO `sis_login` VALUES ('111710169', '5d7845ac6ee7cfffafc5fe5f35cf666d', '2');
INSERT INTO `sis_login` VALUES ('800810596', '5d7845ac6ee7cfffafc5fe5f35cf666d', '2');
-- Comision
INSERT INTO `sis_login` VALUES ('110600492', '5d7845ac6ee7cfffafc5fe5f35cf666d', '3');
INSERT INTO `sis_login` VALUES ('503230754', '5d7845ac6ee7cfffafc5fe5f35cf666d', '3');
INSERT INTO `sis_login` VALUES ('503020651', '5d7845ac6ee7cfffafc5fe5f35cf666d', '3');
-- Estudiantes
INSERT INTO `sis_login` VALUES ('206580363', '5d7845ac6ee7cfffafc5fe5f35cf666d', '4');
INSERT INTO `sis_login` VALUES ('503550224', '5d7845ac6ee7cfffafc5fe5f35cf666d', '4');
INSERT INTO `sis_login` VALUES ('504410118', '5d7845ac6ee7cfffafc5fe5f35cf666d', '4');
INSERT INTO `sis_login` VALUES ('504430777', '5d7845ac6ee7cfffafc5fe5f35cf666d', '4');
INSERT INTO `sis_login` VALUES ('118440202', '5d7845ac6ee7cfffafc5fe5f35cf666d', '4');
INSERT INTO `sis_login` VALUES ('402290345', '5d7845ac6ee7cfffafc5fe5f35cf666d', '4');
INSERT INTO `sis_login` VALUES ('116440018', '5d7845ac6ee7cfffafc5fe5f35cf666d', '4');
-- Asesor
INSERT INTO `sis_login` VALUES ('105710421', '5d7845ac6ee7cfffafc5fe5f35cf666d', '5');
INSERT INTO `sis_login` VALUES ('800870458', '5d7845ac6ee7cfffafc5fe5f35cf666d', '5');
INSERT INTO `sis_login` VALUES ('205830110', '5d7845ac6ee7cfffafc5fe5f35cf666d', '5');
INSERT INTO `sis_login` VALUES ('107010122', '5d7845ac6ee7cfffafc5fe5f35cf666d', '5');
INSERT INTO `sis_login` VALUES ('701810347', '5d7845ac6ee7cfffafc5fe5f35cf666d', '5');

-- ----------------------------
-- Table structure for `sis_user`
-- ----------------------------
DROP TABLE IF EXISTS `sis_user`;
CREATE TABLE `sis_user` (
  `id` varchar(50) NOT NULL,
  `nombre` varchar(150) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `telefono` varchar(15) DEFAULT NULL,
  `id_tipo_tel` varchar(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_sis_login_sis_user` FOREIGN KEY (`id`) REFERENCES `sis_login` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of sis_user
-- ----------------------------

-- Administrador
INSERT INTO `sis_user` VALUES ('205610158', upper('Oscar Chaves Barrantes'), 'oscar.chaves.barrantes@una.cr', '25626370', 'T');
-- Gestor Academico
INSERT INTO `sis_user` VALUES ('111710169', upper('Miguel Arturo Corrales Ureña'), 'miguel.corrales.urena@una.cr', '25626364', 'T');
INSERT INTO `sis_user` VALUES ('800810596', upper('Yamileth Hernandez Cano'), 'yamileth.hernandez.cano@una.ac.cr', '25626367', 'T');
-- Comision
INSERT INTO `sis_user` VALUES ('110600492', upper('Maikol Guzmán Alán'), 'maikol.guzman.alan@una.cr', NULL, NULL);
INSERT INTO `sis_user` VALUES ('503230754', upper('Eddier López López'), 'eddier.lopez.lopez@una.ac.cr', NULL, NULL);
INSERT INTO `sis_user` VALUES ('503020651', upper('Carlos Luis Chanto Espinoza'), 'carlos.chanto.espinoza@una.ac.cr', '83429147', 'M');
-- Estudiantes
INSERT INTO `sis_user` VALUES ('206580363', upper('Miguel Díaz Gutiérrez'), 'miguel.diaz.gutierrez@est.una.ac.cr', '84484757', 'M');
INSERT INTO `sis_user` VALUES ('503550224', upper('Miguel Ángel Rodríguez Arias'), 'miguel.rodriguez.arias@est.una.ac.cr', '84281699', 'M');
INSERT INTO `sis_user` VALUES ('504410118', upper('Carlos Daniel López Chévez'), 'carlos.lopez.chevez@est.una.ac.cr', NULL, NULL);
INSERT INTO `sis_user` VALUES ('504430777', upper('Jose Domingo Molina Salas'), 'jose.molina.salas@est.una.ac.cr', NULL, NULL);
INSERT INTO `sis_user` VALUES ('118440202', upper('Larissa Segura Arguello'), 'larissa.segura.arguello@est.una.ac.cr', NULL, NULL);
INSERT INTO `sis_user` VALUES ('402290345', upper('Esteban Espinoza Fallas'), 'eef251195@gmail.com', NULL, NULL);
INSERT INTO `sis_user` VALUES ('116440018', upper('Marco Antonio Murillo Sánchez'), 'mmurillo532@gmail.com', NULL, NULL);
-- Asesor
INSERT INTO `sis_user` VALUES ('105710421', upper('Georges Alfaro Salazar'), 'georges.alfaro.salazar@una.cr', NULL, NULL);
INSERT INTO `sis_user` VALUES ('800870458', upper('Darinka Grbic Grbic'), 'darinka.grbic.grbic@una.cr', '88373584', 'M');
INSERT INTO `sis_user` VALUES ('205830110', upper('Katty Vásquez Ávila'), 'katty.vasquez.avila@una.cr', '88198417', 'M');
INSERT INTO `sis_user` VALUES ('107010122', upper('Guiselle Víquez Jiménez'), 'guiselle.viquez@gmail.com', '83263459', 'M');
INSERT INTO `sis_user` VALUES ('701810347', upper('Jonathan  Manrique Cordero  Duarte'), 'jcordero1987@gmail.com', '88595127', 'M');

-- ----------------------------

-- Table structure for `sis_mod`
-- ----------------------------
DROP TABLE IF EXISTS `sis_mod`;
CREATE TABLE `sis_mod` (
  `id_mod` int(11) NOT NULL AUTO_INCREMENT,
  `mod_name` varchar(100) DEFAULT NULL,
  `mod_desc` varchar(500) DEFAULT NULL,
  `active` varchar(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_mod`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of sis_mod
-- ----------------------------
INSERT INTO `sis_mod` VALUES ('1', 'Acceso', 'Modulo de acceso al sistema', '1');
INSERT INTO `sis_mod` VALUES ('2', 'Busqueda', 'Modulo de busqueda de proyectos', '1');
INSERT INTO `sis_mod` VALUES ('3', 'Historial y Auditoria', 'Modulo de historial y auditoria de los documentos', '1');
INSERT INTO `sis_mod` VALUES ('4', 'Notificaciones', 'Modulo de notificaciones para el sistema', '1');
INSERT INTO `sis_mod` VALUES ('5', 'Documentacion y versionado', 'Modulo para la documentacion y versionado de los documentos', '1');
INSERT INTO `sis_mod` VALUES ('6', 'Gestion de proyectos', 'Modulo para la gestion de los proyectos', '1');
INSERT INTO `sis_mod` VALUES ('7', 'Reportes y paneles', 'Modulo que permite el acceso a reportes y paneles', '1');
INSERT INTO `sis_mod` VALUES ('8', 'Gestion academica', 'Modulo para la gestion academica del sistema', '1');

-- ----------------------------
-- Table structure for `sis_mod_actions`
-- ----------------------------
DROP TABLE IF EXISTS `sis_mod_actions`;
CREATE TABLE `sis_mod_actions` (
  `id_action` int(11) NOT NULL AUTO_INCREMENT,
  `action_name` varchar(100) DEFAULT NULL,
  `action_desc` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id_action`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of sis_mod_actions
-- ----------------------------
INSERT INTO `sis_mod_actions` VALUES ('1', 'Ver', 'Ver elementos del modulo');
INSERT INTO `sis_mod_actions` VALUES ('2', 'Listar', 'Listar elementos del modulo');
INSERT INTO `sis_mod_actions` VALUES ('3', 'Añadir', 'Añade elementos del modulo');
INSERT INTO `sis_mod_actions` VALUES ('4', 'Editar', 'Modifica elementos del modulo');
INSERT INTO `sis_mod_actions` VALUES ('5', 'Eliminar', 'Elimina elementos del modulo');
INSERT INTO `sis_mod_actions` VALUES ('6', 'Imprimir', 'Permite imprimir elementos del modulo');

-- ----------------------------
-- Table structure for `sis_rolls`
-- ----------------------------
DROP TABLE IF EXISTS `sis_rolls`;
CREATE TABLE `sis_rolls` (
  `id_roll` int(11) NOT NULL AUTO_INCREMENT,
  `roll_name` varchar(100) DEFAULT NULL,
  `roll_desc` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id_roll`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of sis_rolls
-- ----------------------------
INSERT INTO `sis_rolls` VALUES ('1', 'Administrador', 'Permisos totales sobre todos los modulos este usuario no encuentra ninguna restricción');
INSERT INTO `sis_rolls` VALUES ('2', 'Gestor Academico', 'Tiene todos los permisos, excepto los relacionados al control de modulos y permisos de usuarios');
INSERT INTO `sis_rolls` VALUES ('3', 'CTFG', 'Comisión de trabajos finales de graduación, este tiene permisos de lectura sobre los documentos de los estudiantes y puede aprobar o rechazar los trabajos finales de graduación');
INSERT INTO `sis_rolls` VALUES ('4', 'Estudiante', 'Estudiante de la universidad, este tiene permisos de lectura, escritura sobre sus documentos y puede enviar solicitudes de trabajos finales de graduación');
INSERT INTO `sis_rolls` VALUES ('5', 'Asesor', 'Tiene acceso de lectura a los modulos del estudiante');

-- ----------------------------
-- Table structure for `sis_permits`
-- ----------------------------
DROP TABLE IF EXISTS `sis_permits`;
CREATE TABLE `sis_permits` (
  `id_permit` int(11) NOT NULL AUTO_INCREMENT,
  `id_mod` int(11) NOT NULL,
  `id_action` int(11) NOT NULL,
  `id_roll` int(11) NOT NULL,
  PRIMARY KEY (`id_permit`),
  KEY `fk_mod` (`id_mod`),
  KEY `fk_mod_action` (`id_action`),
  KEY `fk_roll` (`id_roll`),
  CONSTRAINT `fk_mod` FOREIGN KEY (`id_mod`) REFERENCES `sis_mod` (`id_mod`),
  CONSTRAINT `fk_mod_action` FOREIGN KEY (`id_action`) REFERENCES `sis_mod_actions` (`id_action`),
  CONSTRAINT `fk_roll` FOREIGN KEY (`id_roll`) REFERENCES `sis_rolls` (`id_roll`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of sis_permits
-- ----------------------------

-- Permisos para Administrador
-- Permisos completos para el rol Administrador (id_roll = 1)
INSERT INTO `sis_permits` (`id_mod`, `id_action`, `id_roll`) VALUES
(1, 1, 1), (1, 2, 1), (1, 3, 1), (1, 4, 1), (1, 5, 1), (1, 6, 1),
(2, 1, 1), (2, 2, 1), (2, 3, 1), (2, 4, 1), (2, 5, 1), (2, 6, 1),
(3, 1, 1), (3, 2, 1), (3, 3, 1), (3, 4, 1), (3, 5, 1), (3, 6, 1),
(4, 1, 1), (4, 2, 1), (4, 3, 1), (4, 4, 1), (4, 5, 1), (4, 6, 1),
(5, 1, 1), (5, 2, 1), (5, 3, 1), (5, 4, 1), (5, 5, 1), (5, 6, 1),
(6, 1, 1), (6, 2, 1), (6, 3, 1), (6, 4, 1), (6, 5, 1), (6, 6, 1),
(7, 1, 1), (7, 2, 1), (7, 3, 1), (7, 4, 1), (7, 5, 1), (7, 6, 1),
(8, 1, 1), (8, 2, 1), (8, 3, 1), (8, 4, 1), (8, 5, 1), (8, 6, 1);

-- Permisos para Gestor Academico
-- Permisos completos para el rol Gestor Academico, excepcion modificacion de modulos (id_roll = 2)
INSERT INTO `sis_permits` (`id_mod`, `id_action`, `id_roll`) VALUES
(1, 1, 2), (1, 2, 2), (1, 6, 2),
(2, 1, 2), (2, 2, 2), (2, 3, 2), (2, 4, 2), (2, 5, 2), (2, 6, 2),
(3, 1, 2), (3, 2, 2), (3, 3, 2), (3, 4, 2), (3, 5, 2), (3, 6, 2),
(4, 1, 2), (4, 2, 2), (4, 3, 2), (4, 4, 2), (4, 5, 2), (4, 6, 2),
(5, 1, 2), (5, 2, 2), (5, 3, 2), (5, 4, 2), (5, 5, 2), (5, 6, 2),
(6, 1, 2), (6, 2, 2), (6, 3, 2), (6, 4, 2), (6, 5, 2), (6, 6, 2),
(7, 1, 2), (7, 2, 2), (7, 3, 2), (7, 4, 2), (7, 5, 2), (7, 6, 2),
(8, 1, 2), (8, 2, 2), (8, 3, 2), (8, 4, 2), (8, 5, 2), (8, 6, 2);

-- Permisos para CTFG
-- Permisos limitados para el rol CTFG (id_roll = 3)
INSERT INTO `sis_permits` (`id_mod`, `id_action`, `id_roll`) VALUES
(1, 1, 3), (1, 3, 3),
(2, 1, 3), (2, 2, 3), (2, 4, 3), (2, 5, 3), (2, 6, 3),
(3, 1, 3), (3, 2, 3), (3, 3, 3), (3, 4, 3), (3, 5, 3), (3, 6, 3),
(4, 1, 3), (4, 2, 3), (4, 3, 3), (4, 4, 3), (4, 5, 3), (4, 6, 3),
(5, 1, 3), (5, 2, 3), (5, 3, 3), (5, 4, 3), (5, 5, 3), (5, 6, 3),
(6, 1, 3), (6, 2, 3), (6, 3, 3), (6, 4, 3), (6, 5, 3), (6, 6, 3),
(7, 1, 3), (7, 2, 3), (7, 6, 3),
(8, 1, 3), (8, 2, 3), (8, 3, 3), (8, 4, 3), (8, 5, 3), (8, 6, 3);

-- Permisos para Estudiante
-- Permisos limitados para el rol Estudiante (id_roll = 4)
INSERT INTO `sis_permits` (`id_mod`, `id_action`, `id_roll`) VALUES
(1, 1, 4),
(3, 1, 4), (3, 2, 4), (3, 6, 4),
(4, 1, 4), (4, 2, 4), (4, 6, 4),
(5, 1, 4), (5, 2, 4), (5, 3, 4), (5, 4, 4), (5, 5, 4), (5, 6, 4),
(6, 1, 4), (6, 3, 4), (6, 4, 4), (6, 6, 4),
(7, 1, 4), (7, 6, 4),
(8, 1, 4), (8, 3, 4), (8, 4, 4);

-- Permisos para Asesor
-- Permisos limitados para el rol Asesor (id_roll = 5)
INSERT INTO `sis_permits` (`id_mod`, `id_action`, `id_roll`) VALUES
(1, 1, 5),
(2, 1, 5),
(3, 1, 5), (3, 2, 5),
(4, 1, 5), (4, 2, 5), (4, 6, 5),
(5, 1, 5),
(6, 1, 5), (6, 3, 5), (6, 4, 5), (6, 6, 5),
(7, 1, 5), (7, 6, 5),
(8, 1, 5);

-- ----------------------------
-- Table structure for `sis_parametros_varios`
-- ----------------------------
DROP TABLE IF EXISTS `sis_parametros_varios`;
CREATE TABLE `sis_parametros_varios` (
  `id_pv` int(16) NOT NULL AUTO_INCREMENT,
  `parametro` varchar(50) DEFAULT NULL COMMENT 'Nombre del parametro',
  `valor` varchar(100) DEFAULT NULL COMMENT 'Valor del parametro',
  `descripcion` varchar(300) DEFAULT NULL,
  PRIMARY KEY (`id_pv`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Tabla para almacenar parametros varios';

-- ----------------------------
-- Records of sis_parametros_varios
-- ----------------------------

-- ----------------------------
-- Table structure for `sis_sessions`
-- ----------------------------
DROP TABLE IF EXISTS `sis_sessions`;
CREATE TABLE `sis_sessions` (
  `sid` varchar(100) NOT NULL DEFAULT '',
  `expires` int(11) unsigned NOT NULL DEFAULT '0',
  `forced_expires` int(11) unsigned NOT NULL,
  `ua` varchar(40) NOT NULL DEFAULT '',
  PRIMARY KEY (`sid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of sis_sessions
-- ----------------------------
INSERT INTO `sis_sessions` VALUES ('0k33888pppuuugg000xxxhhhrroooaaa', '1502488045', '1502489843', '08a5b82c39119cda924c9ad777dbe60f9e545f3a');
INSERT INTO `sis_sessions` VALUES ('1zzz333888lll111ttiiiimmmnnnfffp', '1496522231', '1496523862', '10199e19da728fd0b39a0d684444e7517b55a611');
INSERT INTO `sis_sessions` VALUES ('2eeeaabbbvvvll111777oooaaabbvvvs', '1502488048', '1502489847', '08a5b82c39119cda924c9ad777dbe60f9e545f3a');
INSERT INTO `sis_sessions` VALUES ('db999ll111666ddmmm333ffwwwyyyttt', '1502488050', '1502489848', '08a5b82c39119cda924c9ad777dbe60f9e545f3a');
INSERT INTO `sis_sessions` VALUES ('qccxxxnnfffwwwqqggg000kkhhh77ooo', '1502488047', '1502489845', '08a5b82c39119cda924c9ad777dbe60f9e545f3a');

-- ----------------------------
-- Table structure for `sis_sessions_vars`
-- ----------------------------
DROP TABLE IF EXISTS `sis_sessions_vars`;
CREATE TABLE `sis_sessions_vars` (
  `name` text NOT NULL,
  `value` text NOT NULL,
  `sid` varchar(100) NOT NULL,
  KEY `sid` (`sid`),
  CONSTRAINT `sis_sessions_vars_ibfk_1` FOREIGN KEY (`sid`) REFERENCES `sis_sessions` (`sid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


-- ----------------------------
-- Table structure for `categorias`
-- ----------------------------

CREATE TABLE `categorias` (
  `idCategoria` int(20) NOT NULL,
  `nombre` varchar(20) NOT NULL,
  `categoria` tinyint(1) NOT NULL,
  PRIMARY KEY (idCategoria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


INSERT INTO `categorias` (`idCategoria`, `nombre`, `categoria`) VALUES
(1, 'categoria1', 0),
(2, 'categoria 2', 1);

-- ----------------------------
-- Table structure for `comite`
-- ----------------------------

CREATE TABLE `comite` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `tutor` varchar(50),
  `asesor_1` varchar(50),
  `asesor_2` varchar(50),
  PRIMARY KEY (`Id`),
  KEY `idx_tutor` (`tutor`),
  KEY `idx_asesor_1` (`asesor_1`),
  KEY `idx_asesor_2` (`asesor_2`),
  CONSTRAINT `fk_comite_asesor1` FOREIGN KEY (`asesor_1`) REFERENCES `sis_user` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_comite_asesor2` FOREIGN KEY (`asesor_2`) REFERENCES `sis_user` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_comite_tutor` FOREIGN KEY (`tutor`) REFERENCES `sis_user` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;



INSERT INTO `comite` (`Id`, `tutor`, `asesor_1`, `asesor_2`) VALUES
(7, '105710421', '107010122', '110600492'),
(8, '116440018', '205610158', '206580363'),
(9, '800870458', '503020651', '503230754');



-- ----------------------------
-- Table structure for `proyecto_aprobado`
-- ----------------------------
CREATE TABLE `proyecto_aprobado` (
  `id_aprobado` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `proposal_id` int(11) DEFAULT NULL,
  `comite_id` int(11) NOT NULL,
  `documento` longblob NOT NULL,
  `aprobado` tinyint(1) NOT NULL DEFAULT 1,
  `identificador` varchar(50) NOT NULL,
  `fecha_creacion` datetime NOT NULL,
  `fecha_finalizacion` datetime NOT NULL,
  PRIMARY KEY (`id_aprobado`),
  UNIQUE KEY `uq_identificador` (`identificador`),
  KEY `idx_comite_id` (`comite_id`),
  KEY `idx_proposal_id` (`proposal_id`),
  CONSTRAINT `fk_proy_comite` FOREIGN KEY (`comite_id`) REFERENCES `comite` (`Id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_proy_proposal` FOREIGN KEY (`proposal_id`) REFERENCES `tfg_proposals` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;


-- ----------------------------
-- Table structure for `proyecto_aprobado_estudiantes`
-- ----------------------------

CREATE TABLE `proyecto_aprobado_estudiantes` (
  `id_aprobado` int(11) NOT NULL,
  `estudiante_id` varchar(50) NOT NULL,
  PRIMARY KEY (`id_aprobado`,`estudiante_id`),
  KEY `fk_pae_estudiante` (`estudiante_id`),
  CONSTRAINT `fk_pae_estudiante` FOREIGN KEY (`estudiante_id`) REFERENCES `sis_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pae_proyecto` FOREIGN KEY (`id_aprobado`) REFERENCES `proyecto_aprobado` (`id_aprobado`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;


-- ----------------------------
-- Table structure for `proyecto_notas`
-- ----------------------------

CREATE TABLE `proyecto_notas` (
  `id_nota` int(11) NOT NULL,
  `proyecto_id` int(11) NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `notas` text NOT NULL,
  `creado_por` varchar(50) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `etapa_proyecto` varchar(100) DEFAULT NULL,
  KEY `proyecto_id` (`proyecto_id`),
  CONSTRAINT `proyecto_notas_ibfk_1` FOREIGN KEY (`proyecto_id`) REFERENCES `proyecto_aprobado` (`id_aprobado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

ALTER TABLE proyecto_aprobado
ADD COLUMN estado VARCHAR(20) NOT NULL DEFAULT 'ACTIVO';

ALTER TABLE proyecto_aprobado
ADD COLUMN fecha_ultimo_avance DATETIME NULL;

-- --------------------------------
-- Tabla de acuerdos de cancelacion
-- --------------------------------


CREATE TABLE acuerdo_cancelacion (
  id INT AUTO_INCREMENT PRIMARY KEY,
  proyecto_id INT NOT NULL,
  usuario_id VARCHAR(50) NOT NULL,
  motivo VARCHAR(500) NOT NULL,
  observaciones TEXT,
  fecha_cancelacion DATETIME NOT NULL,
  fecha_ultimo_avance_usada DATETIME,
  UNIQUE KEY uq_cancelacion_proyecto (proyecto_id),
  CONSTRAINT fk_cancelacion_proyecto
    FOREIGN KEY (proyecto_id)
    REFERENCES proyecto_aprobado(id_aprobado)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tfg_extension_requests`
--

CREATE TABLE `tfg_extension_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `proposal_id` int(11) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `extension_number` tinyint(4) NOT NULL COMMENT '1=Primera prórroga (1 año), 2=Segunda prórroga (6 meses)',
  `reason` text NOT NULL,
  `status` enum('pendiente','aprobada','rechazada') DEFAULT 'pendiente',
  `request_date` datetime DEFAULT current_timestamp(),
  `response_date` datetime DEFAULT NULL,
  `responded_by` varchar(50) DEFAULT NULL,
  `response_comment` text DEFAULT NULL,
  `documento_path` text DEFAULT NULL COMMENT 'Rutas de los documentos de soporte (JSON array)',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Table structure for `tfg_proposal_history`
-- ----------------------------
DROP TABLE IF EXISTS `tfg_proposal_history`;
CREATE TABLE `tfg_proposal_history` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proposal_id INT NOT NULL,
    document LONGBLOB NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    file_size INT UNSIGNED NOT NULL,
    status VARCHAR(50) NOT NULL,
    reviewed_by VARCHAR(50) DEFAULT NULL,
    comments TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (proposal_id) REFERENCES tfg_proposals(id),
    FOREIGN KEY (reviewed_by) REFERENCES sis_user(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;


-- =====================================================
-- TABLA: auditoria_cambios_fecha
-- Propósito: Registrar modificaciones en fechas de proyectos y prórrogas
-- Tablas auditadas: registered_projects, tfg_extension_requests
-- =====================================================

CREATE TABLE `auditoria_cambios_fecha` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tabla_origen` VARCHAR(100) NOT NULL COMMENT 'Tabla donde se hizo el cambio (registered_projects, tfg_extension_requests)',
    `id_registro` INT NOT NULL COMMENT 'ID del registro modificado',
    `campo_modificado` VARCHAR(100) NOT NULL COMMENT 'Nombre del campo de fecha que cambió',
    `valor_anterior` DATETIME DEFAULT NULL COMMENT 'Valor antes del cambio',
    `valor_nuevo` DATETIME DEFAULT NULL COMMENT 'Valor después del cambio',
    `modificado_por` VARCHAR(50) NOT NULL COMMENT 'ID del usuario que realizó el cambio',
    `fecha_modificacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora del cambio',
    `descripcion` TEXT DEFAULT NULL COMMENT 'Descripción adicional o contexto del cambio',
    
    INDEX `idx_tabla_origen` (`tabla_origen`),
    INDEX `idx_id_registro` (`id_registro`),
    INDEX `idx_modificado_por` (`modificado_por`),
    INDEX `idx_fecha_modificacion` (`fecha_modificacion`),
    INDEX `idx_campo_modificado` (`campo_modificado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Auditoría de cambios en fechas de proyectos y prórrogas';



-- ----------------------------
-- Table structure for `sis_tipo_tel`
-- ----------------------------
DROP TABLE IF EXISTS `sis_tipo_tel`;
CREATE TABLE `sis_tipo_tel` (
  `id_tipo_tel` varchar(1) NOT NULL DEFAULT '',
  `desc_tipo_tel` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id_tipo_tel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of sis_tipo_tel
-- ----------------------------
INSERT INTO `sis_tipo_tel` VALUES ('C', 'Casa');
INSERT INTO `sis_tipo_tel` VALUES ('F', 'Fax');
INSERT INTO `sis_tipo_tel` VALUES ('M', 'Movil');
INSERT INTO `sis_tipo_tel` VALUES ('T', 'Trabajo');

-- ----------------------------
-- View structure for `vis_user`
-- ----------------------------
DROP VIEW IF EXISTS `vis_user`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vis_user` AS select `a`.`id` AS `id`,`b`.`nombre` AS `nombre`,(select `sis_rolls`.`roll_name` from `sis_rolls` where (`sis_rolls`.`id_roll` = `a`.`id_roll`)) AS `roll` from (`sis_login` `a` join `sis_user` `b`) where (`a`.`id` = `b`.`id`) ;

-- ----------------------------
-- Procedure structure for `delete_mod`
-- ----------------------------
DROP PROCEDURE IF EXISTS `delete_mod`;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `delete_mod`(IN `p_id_mod` int,OUT `respuesta` int)
BEGIN







DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET respuesta=0;







START TRANSACTION;



UPDATE sis_mod 



SET  active='0'



WHERE id_mod=p_id_mod;



SELECT ROW_COUNT() INTO respuesta;







IF (respuesta=1) THEN



                                        COMMIT;



ELSE



                                        ROLLBACK;



                                        SET respuesta=0;



END IF;







END
;;
DELIMITER ;

-- ----------------------------
-- Procedure structure for `delete_roll`
-- ----------------------------
DROP PROCEDURE IF EXISTS `delete_roll`;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `delete_roll`(IN `p_id_roll` int)
BEGIN







CALL  delete_roll_permits(p_id_roll);



DELETE FROM sis_rolls



WHERE id_roll=p_id_roll;







END
;;
DELIMITER ;

-- ----------------------------
-- Procedure structure for `delete_roll_permits`
-- ----------------------------
DROP PROCEDURE IF EXISTS `delete_roll_permits`;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `delete_roll_permits`(IN `p_id_roll` int)
BEGIN







DELETE FROM sis_permits



WHERE id_roll=p_id_roll;







END
;;
DELIMITER ;

-- ----------------------------
-- Procedure structure for `delete_user`
-- ----------------------------
DROP PROCEDURE IF EXISTS `delete_user`;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `delete_user`(IN `p_id` varchar(50),OUT `res` tinyint unsigned)
BEGIN
	DECLARE EXIT HANDLER FOR SQLEXCEPTION
	BEGIN
		-- ERROR
    SET res = 1;
    ROLLBACK;
	END;

  DECLARE EXIT HANDLER FOR SQLWARNING
	BEGIN
		-- ERROR
    SET res = 2;
    ROLLBACK;
	END;

	START TRANSACTION ;
		DELETE FROM sis_user WHERE id=p_id;
		DELETE FROM sis_login WHERE id=p_id;
	COMMIT;
	-- SUCCESS
	SET res = 0;
END
;;
DELIMITER ;

-- ----------------------------
-- Procedure structure for `exploit`
-- ----------------------------
DROP PROCEDURE IF EXISTS `exploit`;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `exploit`(INOUT `pcadena` varchar(5000),IN `separador` varchar(1),OUT `vtexto` varchar(5000))
BEGIN



set vtexto = substring(pcadena, 1, instr(pcadena, separador)-1);



set pcadena = substring(pcadena, instr(pcadena, separador)+1);



END
;;
DELIMITER ;

-- ----------------------------
-- Procedure structure for `insert_log`
-- ----------------------------
DROP PROCEDURE IF EXISTS `insert_log`;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `insert_log`(IN `p_id_user` varchar(50),IN `p_detail` text,OUT `res` tinyint)
BEGIN
	DECLARE EXIT HANDLER FOR SQLEXCEPTION
	BEGIN
		-- ERROR
		SET res = 1;
		ROLLBACK;
	END;

	DECLARE EXIT HANDLER FOR SQLWARNING
	BEGIN
		-- ERROR
		SET res = 2;
		ROLLBACK;
	END;

	START TRANSACTION;
    INSERT INTO `sis_log`(id_user,date_bi,action_type,action_result,detail) VALUES (p_id_user,NOW(),'SYSTEM_CHANGE','SUCCESS',p_detail);
	COMMIT;
	
	-- SUCCESS
	SET res = 0;
END
;;
DELIMITER ;

-- ----------------------------
-- Procedure structure for `insert_access_log`
-- ----------------------------
DROP PROCEDURE IF EXISTS `insert_access_log`;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `insert_access_log`(
  IN `p_id_user` varchar(50),
  IN `p_action_type` varchar(50),
  IN `p_action_result` varchar(20),
  IN `p_ip_address` varchar(45),
  IN `p_device_info` varchar(255),
  IN `p_detail` text,
  OUT `res` tinyint
)
BEGIN
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    SET res = 1;
    ROLLBACK;
  END;

  DECLARE EXIT HANDLER FOR SQLWARNING
  BEGIN
    SET res = 2;
    ROLLBACK;
  END;

  START TRANSACTION;
    INSERT INTO `sis_log`(
      id_user,
      date_bi,
      action_type,
      action_result,
      ip_address,
      device_info,
      detail
    ) VALUES (
      p_id_user,
      NOW(),
      p_action_type,
      p_action_result,
      p_ip_address,
      p_device_info,
      p_detail
    );
  COMMIT;

  SET res = 0;
END
;;
DELIMITER ;

-- ----------------------------
-- Procedure structure for `purge_old_sis_log`
-- ----------------------------
DROP PROCEDURE IF EXISTS `purge_old_sis_log`;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `purge_old_sis_log`(OUT `deleted_rows` int)
BEGIN
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SET @sis_log_allow_purge = 0;
    SET deleted_rows = -1;
  END;

  START TRANSACTION;
    SET @sis_log_allow_purge = 1;
    DELETE FROM `sis_log`
    WHERE `date_bi` < DATE_SUB(NOW(), INTERVAL 5 YEAR)
    LIMIT 10000;
    SET deleted_rows = ROW_COUNT();
    SET @sis_log_allow_purge = 0;
  COMMIT;
END
;;
DELIMITER ;

-- ----------------------------
-- Event structure for `evt_purge_old_sis_log`
-- ----------------------------
DROP EVENT IF EXISTS `evt_purge_old_sis_log`;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` EVENT `evt_purge_old_sis_log`
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP + INTERVAL 1 DAY
DO
BEGIN
  CALL `purge_old_sis_log`(@deleted_rows);
END
;;
DELIMITER ;

-- ----------------------------
-- Procedure structure for `delete_sis_log_by_admin`
-- ----------------------------
DROP PROCEDURE IF EXISTS `delete_sis_log_by_admin`;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `delete_sis_log_by_admin`(
	IN `p_admin_user_id` varchar(50),
	IN `p_date_before` datetime,
	OUT `deleted_rows` int,
	OUT `res` tinyint
)
BEGIN
	DECLARE EXIT HANDLER FOR SQLEXCEPTION
	BEGIN
		-- ERROR en transaction
		ROLLBACK;
		SET deleted_rows = 0;
		SET res = 3;
	END;

	DECLARE EXIT HANDLER FOR SQLWARNING
	BEGIN
		-- WARNING en transaction
		ROLLBACK;
		SET deleted_rows = 0;
		SET res = 3;
	END;

	-- Validar que el usuario es administrador (id_roll = 1)
	IF NOT EXISTS (
		SELECT 1 FROM sis_user 
		WHERE id = p_admin_user_id AND id_roll = 1
	) THEN
		-- El usuario NO es administrador
		SET deleted_rows = 0;
		SET res = 2;
	ELSE
		-- El usuario es administrador, proceder con la eliminación
		START TRANSACTION;
		
		-- Permitir eliminación usando la variable de sesión
		SET @sis_log_allow_purge_admin = 1;
		
		-- Eliminar registros anteriores a la fecha especificada (máximo 50000)
		DELETE FROM sis_log
		WHERE date_bi < p_date_before
		LIMIT 50000;
		
		-- Obtener el número de filas eliminadas
		SELECT ROW_COUNT() INTO deleted_rows;
		
		-- Resetear la variable de sesión
		SET @sis_log_allow_purge_admin = 0;
		
		COMMIT;
		
		-- Retornar código de éxito
		SET res = 0;
	END IF;
END
;;
DELIMITER ;

-- ----------------------------
-- Procedure structure for `insert_mod`
-- ----------------------------
DROP PROCEDURE IF EXISTS `insert_mod`;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `insert_mod`(IN `p_name_mod` varchar(100),IN `p_desc_mod` varchar(500),OUT `respuesta` int)
BEGIN







DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET respuesta=0;







START TRANSACTION;







INSERT INTO sis_mod (mod_name,mod_desc)



VALUES (p_name_mod,p_desc_mod);



SELECT ROW_COUNT() INTO respuesta;







IF (respuesta=1) THEN



                                        COMMIT;



ELSE



                                        ROLLBACK;



                                        SET respuesta=0;



END IF;







END
;;
DELIMITER ;

-- ----------------------------
-- Procedure structure for `insert_roll_permits`
-- ----------------------------
DROP PROCEDURE IF EXISTS `insert_roll_permits`;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `insert_roll_permits`(IN `p_id_roll` int,IN `p_cadena` varchar(500))
BEGIN



	DECLARE vmodulo varchar(5000);



  DECLARE vpermiso varchar(5000);



  DECLARE vaccion varchar(5000);



  BEGIN



        WHILE (p_cadena != "") DO



            BEGIN



            CALL exploit(p_cadena, 'm', vmodulo);



                WHILE (p_cadena != "" AND instr(substr(p_cadena, 1, if(instr(p_cadena, "m") = 0, instr(concat(p_cadena, "m"), "m")-1, instr(p_cadena, "m")-1)), "a") > 0) DO



                    BEGIN



                        CALL exploit(p_cadena, 'a', vaccion);



                        INSERT INTO sis_permits (id_roll, id_mod, id_action)



                        VALUES (p_id_roll, vmodulo, vaccion);



                    END;



                END WHILE;



            END;



        END WHILE;



    END;



END
;;
DELIMITER ;

-- ----------------------------
-- Procedure structure for `insert_user`
-- ----------------------------
DROP PROCEDURE IF EXISTS `insert_user`;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `insert_user`(IN `p_id` varchar(50),IN `p_nombre` varchar(150),IN `p_email` varchar(100),IN `p_telefono` varchar(15),IN `p_id_tipo_tel` varchar(1),IN `p_id_roll` int,IN `p_pass` varchar(255),OUT `res` TINYINT  UNSIGNED)
BEGIN
	DECLARE EXIT HANDLER FOR SQLEXCEPTION
	BEGIN
		-- ERROR
    SET res = 1;
    ROLLBACK;
	END;

  DECLARE EXIT HANDLER FOR SQLWARNING
	BEGIN
		-- ERROR
    SET res = 2;
    ROLLBACK;
	END;

	SELECT 	count(a.id) INTO @cantidad FROM sis_user  a, sis_login b WHERE a.id=p_id AND b.id=p_id;
	IF (@cantidad = 0) THEN
		START TRANSACTION;
			INSERT INTO `sis_login`(id, pass, id_roll) VALUES (p_id, p_pass, p_id_roll);
			INSERT INTO `sis_user`(id, nombre, email,telefono, id_tipo_tel) VALUES (p_id, p_nombre, p_email,p_telefono, p_id_tipo_tel);
		COMMIT;
		-- SUCCESS
		SET res = 0;
	ELSE
		-- Existe usuario
		SET res = 3;
	END IF;
END
;;
DELIMITER ;

-- ----------------------------
-- Procedure structure for `update_mod`
-- ----------------------------
DROP PROCEDURE IF EXISTS `update_mod`;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `update_mod`(IN `p_id_mod` int,IN `p_mod_name` varchar(100),IN `p_mod_desc` varchar(500),OUT `respuesta` int)
BEGIN







DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET respuesta=0;







START TRANSACTION;



UPDATE sis_mod 



SET  mod_name=p_mod_name,



         mod_desc=p_mod_desc



WHERE id_mod=p_id_mod;



SELECT ROW_COUNT() INTO respuesta;







IF (respuesta=1) THEN



                                        COMMIT;



ELSE



                                        ROLLBACK;



                                        SET respuesta=0;



END IF;







END
;;
DELIMITER ;

-- ----------------------------
-- Procedure structure for `update_perfil`
-- ----------------------------
DROP PROCEDURE IF EXISTS `update_perfil`;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `update_perfil`(IN `p_id` varchar(50),IN `p_email` varchar(100),IN `p_telefono` varchar(15),IN `p_id_tipo_tel` varchar(1),IN `p_pass` varchar(255),OUT `res` TINYINT  UNSIGNED)
BEGIN
	DECLARE EXIT HANDLER FOR SQLEXCEPTION
	BEGIN
		-- ERROR
    SET res = 1;
    ROLLBACK;
	END;

  DECLARE EXIT HANDLER FOR SQLWARNING
	BEGIN
		-- ERROR
    SET res = 2;
    ROLLBACK;
	END;
	START TRANSACTION;
		UPDATE `sis_login`
		SET pass = p_pass
		WHERE id=P_id;
		UPDATE `sis_user`
		SET email = p_email,
						telefono = p_telefono,
						id_tipo_tel = p_id_tipo_tel
		WHERE id=P_id;
	COMMIT;
	-- SUCCESS
	SET res = 0;
END
;;
DELIMITER ;

-- ----------------------------
-- Procedure structure for `update_roll`
-- ----------------------------
DROP PROCEDURE IF EXISTS `update_roll`;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `update_roll`(IN `p_id_roll` int,IN `p_roll_name` varchar(100),IN `p_roll_desc` varchar(500),OUT `respuesta` int)
BEGIN







DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET respuesta=0;







START TRANSACTION;



UPDATE sis_rolls 



SET  roll_name=p_roll_name,



         roll_desc=p_roll_desc



WHERE id_roll=p_id_roll;



SELECT ROW_COUNT() INTO respuesta;







IF (respuesta=1) THEN



                                        COMMIT;



ELSE



                                        ROLLBACK;



                                        SET respuesta=0;



END IF;







END
;;
DELIMITER ;

-- ----------------------------
-- Procedure structure for `update_user`
-- ----------------------------
DROP PROCEDURE IF EXISTS `update_user`;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `update_user`(IN `p_id` varchar(50),IN `p_nombre` varchar(150),IN `p_email` varchar(100),IN `p_telefono` varchar(15),IN `p_id_tipo_tel` varchar(1),IN `p_id_roll` int,OUT `res` TINYINT  UNSIGNED)
BEGIN
	DECLARE EXIT HANDLER FOR SQLEXCEPTION
	BEGIN
		-- ERROR
    SET res = 1;
    ROLLBACK;
	END;

  DECLARE EXIT HANDLER FOR SQLWARNING
	BEGIN
		-- ERROR
    SET res = 2;
    ROLLBACK;
	END;
	START TRANSACTION;
		UPDATE `sis_login`
		SET id_roll = p_id_roll
		WHERE id=P_id;
		UPDATE `sis_user`
		SET nombre = p_nombre,
						email = p_email,
						telefono = p_telefono,
						id_tipo_tel = p_id_tipo_tel
		WHERE id=P_id;
	COMMIT;
	-- SUCCESS
	SET res = 0;
END
;;
DELIMITER ;

-- ----------------------------
-- Function structure for `check_permits`
-- ----------------------------
DROP FUNCTION IF EXISTS `check_permits`;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `check_permits`(`p_id_mod` int,`p_id_action` int,`p_id_roll` int) RETURNS int(1)
BEGIN

	DECLARE 

		li_out int(1);

	IF(p_id_roll=1) THEN

		RETURN 1;

	ELSE

		SELECT COUNT(id_roll) INTO li_out

		FROM sis_permits 

		WHERE id_mod = p_id_mod

			AND id_action = p_id_action

			AND id_roll = p_id_roll ;

		RETURN li_out;

	END IF;

END
;;
DELIMITER ;

-- ----------------------------
-- Function structure for `checklogin`
-- ----------------------------
DROP FUNCTION IF EXISTS `checklogin`;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `checklogin`(`p_id` varchar(50),`p_pass` varchar(255)) RETURNS int(1)
BEGIN
	DECLARE
		li_out int(1);
	DECLARE
		li_user int(1);
	DECLARE
		li_pass int(1);
	BEGIN
		SELECT count(id) INTO li_user
		FROM sis_login
		WHERE id = p_id;
	END;
	BEGIN
		SELECT count(id) INTO li_pass
		FROM sis_login
		WHERE id = p_id
			AND pass = p_pass;
	END;
	IF(li_user=0) THEN
		SET li_out =2; -- noexiste usuario
	ELSE 
		IF (li_pass=0) THEN
			SET li_out =1; -- pass erroneo
		ELSE
			SET li_out = 0; -- todo está bien
		END IF;
	END IF;
	RETURN li_out;
END
;;
DELIMITER ;

-- ----------------------------
-- Function structure for `insert_roll`
-- ----------------------------
DROP FUNCTION IF EXISTS `insert_roll`;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `insert_roll`(`p_roll_name` varchar(100),`p_roll_desc` varchar(500)) RETURNS int(11)
BEGIN



	DECLARE



		li_out int(11);



	



		INSERT INTO sis_rolls (roll_name,roll_desc)



		VALUES (p_roll_name,p_roll_desc);



		SELECT ROW_COUNT() INTO li_out;







		IF (li_out=1) THEN



			SELECT IF(id_roll = '', 0, id_roll) INTO li_out



			FROM sis_rolls



			WHERE roll_name = p_roll_name



				AND roll_desc= p_roll_desc;



		ELSE



			SET li_out=0;



		END IF;







	RETURN li_out;



END
;;
DELIMITER ;

-- ============================================================================
-- NUEVAS TABLAS PARA SISTEMA TFG COMPLETO
-- ============================================================================

-- ----------------------------
-- Drop existing tables in correct order (to avoid foreign key constraints)
-- ----------------------------
DROP TABLE IF EXISTS `project_history`;
DROP TABLE IF EXISTS `project_members`;
DROP TABLE IF EXISTS `registered_projects`;
DROP TABLE IF EXISTS `tfg_proposals`;
DROP TABLE IF EXISTS `project_types`;

-- ----------------------------
-- TABLA 1: TIPOS DE PROYECTO
-- ----------------------------
CREATE TABLE project_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type_name VARCHAR(100) NOT NULL,
    max_members INT NOT NULL DEFAULT 1,
    description TEXT NULL,
    active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ----------------------------
-- TABLA 2: PROPUESTAS TFG
-- ----------------------------
CREATE TABLE tfg_proposals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
    title VARCHAR(255) NOT NULL,
    disciplines VARCHAR(255) NOT NULL,
    project_description TEXT NULL,
    document LONGBLOB NULL,
    file_name VARCHAR(255) NOT NULL DEFAULT '',
    mime_type VARCHAR(100) NOT NULL DEFAULT '',
    file_size INT NOT NULL DEFAULT 0,
    status ENUM('Pendiente de Revision', 'Cumple requisitos', 'No cumple requisitos', 'Aprobado', 'Rechazado') DEFAULT 'Pendiente de Revision',
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



-- ----------------------------
-- TABLA 3: PROYECTOS REGISTRADOS
-- ----------------------------
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

-- ----------------------------
-- TABLA 4: MIEMBROS DE PROYECTO
-- ----------------------------
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

-- ----------------------------
-- TABLA 5: HISTORIAL DE PROYECTOS
-- ----------------------------
CREATE TABLE project_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
    action_type ENUM('Creado', 'Modificado', 'Miembro Agregado', 'Miembro Removido', 'Estado Cambiado') NOT NULL,
    old_value TEXT NULL,
    new_value TEXT NULL,
    comments TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_project_id (project_id),
    INDEX idx_user_id (user_id),
    INDEX idx_action (action_type),
    INDEX idx_created (created_at),
    CONSTRAINT fk_project_history_project FOREIGN KEY (project_id) REFERENCES registered_projects(id) ON DELETE CASCADE,
    CONSTRAINT fk_project_history_user FOREIGN KEY (user_id) REFERENCES sis_user(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ----------------------------
-- DATOS INICIALES: TIPOS DE PROYECTO
-- ----------------------------
INSERT INTO project_types (type_name, max_members, description) VALUES
('Tesis', 2, 'Trabajo individual o en parejas'),
('Proyecto de Graduación', 3, 'Proyecto grupal de hasta 3 integrantes'),
('Seminario', 8, 'Trabajo grupal de hasta 8 integrantes');

-- ----------------------------
-- Datos semilla: propuesta TFG 2024 con comite #9
-- ----------------------------
INSERT INTO tfg_proposals (
  user_id,
  title,
  disciplines,
  project_description,
  document,
  file_name,
  mime_type,
  file_size,
  status,
  admin_comments,
  reviewed_by,
  reviewed_at,
  created_at,
  updated_at
) VALUES (
  '504410118',
  'Desarrollo de expediente clinico digital para optimizar el programa de rehabilitacion cardiaca y cerebrovascular de la Universidad Nacional Sede Chorotega Campus Liberia',
  'Desarrollo de Sistemas basados en WEB',
  'Desarrollo de expediente clinico digital para optimizar el programa de rehabilitacion cardiaca y cerebrovascular de la Universidad Nacional Sede Chorotega Campus Liberia',
  LOAD_FILE('C:/xampp/htdocs/base/README.pdf'),
  'README.pdf',
  'application/pdf',
  143723,
  'Aprobado',
  'Datos base 2024. Acuerdos asociados visibles en la fuente: UNA-CTFG-EI-ACUE-038-2024, UNA-CTFG-EI-ACUE-056-2024 y UNA-CTFG-EI-ACUE-064-2024.',
  '111710169',
  '2024-10-04 00:00:00',
  '2024-06-26 00:00:00',
  '2024-10-04 00:00:00'
);

SET @seed_proposal_2024_id := LAST_INSERT_ID();

INSERT INTO registered_projects (
  tfg_proposal_id,
  project_type_id,
  status,
  start_date,
  end_date,
  final_grade,
  supervisor_id,
  created_at,
  updated_at
) VALUES (
  @seed_proposal_2024_id,
  2,
  'En Desarrollo',
  '2024-06-26',
  '2025-10-04',
  NULL,
  '800870458',
  '2024-06-26 00:00:00',
  '2024-10-04 00:00:00'
);

SET @seed_registered_project_2024_id := LAST_INSERT_ID();

INSERT INTO project_members (
  project_id,
  user_id,
  role,
  status,
  joined_at,
  left_at
) VALUES
(
  @seed_registered_project_2024_id,
  '504410118',
  'Líder',
  'Activo',
  '2024-06-26 00:00:00',
  NULL
),
(
  @seed_registered_project_2024_id,
  '504430777',
  'Miembro',
  'Activo',
  '2024-06-26 00:00:00',
  NULL
),
(
  @seed_registered_project_2024_id,
  '118440202',
  'Miembro',
  'Activo',
  '2024-06-26 00:00:00',
  NULL
);

INSERT INTO proyecto_aprobado (
  nombre,
  proposal_id,
  comite_id,
  documento,
  aprobado,
  identificador,
  fecha_creacion,
  fecha_finalizacion
) VALUES (
  'Desarrollo de expediente clinico digital para optimizar el programa de rehabilitacion cardiaca y cerebrovascular de la Universidad Nacional Sede Chorotega Campus Liberia',
  @seed_proposal_2024_id,
  9,
  '',
  1,
  'UNA-CTFG-EI-ACUE-064-2024',
  '2024-10-04 00:00:00',
  '2025-10-04 00:00:00'
);

SET @seed_approved_project_2024_id := LAST_INSERT_ID();

INSERT INTO proyecto_aprobado_estudiantes (id_aprobado, estudiante_id) VALUES
(@seed_approved_project_2024_id, '504410118'),
(@seed_approved_project_2024_id, '504430777'),
(@seed_approved_project_2024_id, '118440202');

-- ============================================
-- TABLAS PARA DOCUMENTOS FINALES DE TFG (HU-014)
-- ============================================

-- ----------------------------
-- Table structure for `tfg_files`
-- Almacena los archivos (BLOB) separados para mejor rendimiento
-- ----------------------------
DROP TABLE IF EXISTS `tfg_files`;
CREATE TABLE `tfg_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file_name` varchar(255) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `file_size` bigint(20) NOT NULL,
  `file_data` longblob NOT NULL,
  `storage_path` varchar(500) DEFAULT NULL,
  `uploaded_by` varchar(50) NOT NULL,
  `upload_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `version` FLOAT NOT NULL DEFAULT 1,
  `document_type` VARCHAR(50) NOT NULL,
  `proposal_id` int(11) DEFAULT NULL COMMENT 'FK a tfg_proposals (archivo de propuesta)',
  `final_document_id` int(11) DEFAULT NULL COMMENT 'FK a tfg_final_documents (archivo de documento final)',
  PRIMARY KEY (`id`),
  KEY `idx_upload_date` (`upload_date`),
  KEY `fk_tfg_files_user` (`uploaded_by`),
  KEY `idx_tfg_files_proposal` (`proposal_id`),
  KEY `idx_tfg_files_final_document` (`final_document_id`),
  CONSTRAINT `fk_tfg_files_user` FOREIGN KEY (`uploaded_by`) REFERENCES `sis_user` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_tfg_files_proposal` FOREIGN KEY (`proposal_id`) REFERENCES `tfg_proposals` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ----------------------------
-- Table structure for `tfg_final_documents`
-- Almacena la metadata de los documentos finales de TFG
-- NOTA: La validación de estructura (capítulos, formato APA, firma del tutor) 
--       es MANUAL por la CTFG, no se almacena en el sistema
-- ----------------------------
DROP TABLE IF EXISTS `tfg_final_documents`;
CREATE TABLE `tfg_final_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `proposal_id` int(11) NOT NULL,
  `file_id` int(11) NOT NULL,
  `status` enum('Pendiente de Revision','En Revision','Aprobado','Rechazado') DEFAULT 'Pendiente de Revision',
  `project_status` enum('Vigente','Prorroga Activa','Vencido') NOT NULL,
  `submitted_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `submitted_by` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_proposal_final` (`proposal_id`),
  KEY `idx_status` (`status`),
  KEY `idx_submitted_at` (`submitted_at`),
  KEY `fk_tfg_final_file` (`file_id`),
  KEY `fk_tfg_final_submitter` (`submitted_by`),
  CONSTRAINT `fk_tfg_final_proposal` FOREIGN KEY (`proposal_id`) REFERENCES `tfg_proposals` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_tfg_final_file` FOREIGN KEY (`file_id`) REFERENCES `tfg_files` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_tfg_final_submitter` FOREIGN KEY (`submitted_by`) REFERENCES `sis_user` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Vincular tfg_files con tfg_final_documents (FK diferida por dependencia circular)
ALTER TABLE `tfg_files`
  ADD CONSTRAINT `fk_tfg_files_final_document`
    FOREIGN KEY (`final_document_id`) REFERENCES `tfg_final_documents` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE;

-- ----------------------------
-- Table structure for `tfg_document_reviews`
-- Almacena el historial de revisiones de documentos finales TFG (HU-020)
-- Incluye tanto revisiones del CTFG como correcciones del estudiante
-- ----------------------------
DROP TABLE IF EXISTS `tfg_document_reviews`;
CREATE TABLE `tfg_document_reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `document_id` int(11) NOT NULL COMMENT 'FK a tfg_final_documents',
  `file_version` int(11) NOT NULL COMMENT 'Versión del archivo revisado',
  `reviewer_id` varchar(50) DEFAULT NULL COMMENT 'ID del revisor (CTFG), NULL si es corrección del estudiante',
  `review_type` enum('Revision CTFG','Correccion Estudiante') NOT NULL COMMENT 'Tipo de revisión',
  `status` enum('Aprobado','Rechazado','Pendiente de Revision') NOT NULL COMMENT 'Estado de la revisión',
  `corrections_summary` text DEFAULT NULL COMMENT 'Resumen de correcciones solicitadas o realizadas',
  `corrections_count` int(11) DEFAULT 0 COMMENT 'Contador de ciclos de corrección',
  `reviewed_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de la revisión',
  PRIMARY KEY (`id`),
  KEY `idx_document_id` (`document_id`),
  KEY `idx_reviewer_id` (`reviewer_id`),
  KEY `idx_review_type` (`review_type`),
  KEY `idx_reviewed_at` (`reviewed_at`),
  CONSTRAINT `fk_review_document` FOREIGN KEY (`document_id`) REFERENCES `tfg_final_documents` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_review_reviewer` FOREIGN KEY (`reviewer_id`) REFERENCES `sis_user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ----------------------------
-- Table structure for `tfg_project_timeline`
-- Almacena las fechas y estado del proyecto según Art. 73 RGPEA
-- ----------------------------
DROP TABLE IF EXISTS `tfg_project_timeline`;
CREATE TABLE `tfg_project_timeline` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `proposal_id` int(11) NOT NULL,
  `approval_date` date NOT NULL,
  `original_deadline` date NOT NULL COMMENT '1 año (12 meses) desde aprobación',
  `status` enum('Vigente','Prorroga Activa','Vencido') NOT NULL DEFAULT 'Vigente',
  `days_remaining` int(11) DEFAULT NULL,
  `last_updated` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_proposal_timeline` (`proposal_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_tfg_timeline_proposal` FOREIGN KEY (`proposal_id`) REFERENCES `tfg_proposals` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table structure for `tfg_notifications`
-- Almacena las notificaciones para la CTFG
-- ----------------------------
DROP TABLE IF EXISTS `tfg_notifications`;
CREATE TABLE `tfg_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `notification_type` enum('Documento Final Subido') NOT NULL,
  `proposal_id` int(11) NOT NULL,
  `sender_id` varchar(50) NOT NULL,
  `recipient_role_id` int(11) NOT NULL COMMENT '3 = CTFG',
  `message` text NOT NULL,
  `status` enum('Pendiente','Enviada') DEFAULT 'Pendiente',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_recipient` (`recipient_role_id`,`status`),
  KEY `fk_tfg_notif_proposal` (`proposal_id`),
  KEY `fk_tfg_notif_sender` (`sender_id`),
  CONSTRAINT `fk_tfg_notif_proposal` FOREIGN KEY (`proposal_id`) REFERENCES `tfg_proposals` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_tfg_notif_sender` FOREIGN KEY (`sender_id`) REFERENCES `sis_user` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_tfg_notif_role` FOREIGN KEY (`recipient_role_id`) REFERENCES `sis_rolls` (`id_roll`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;


-- ----------------------------
-- Table structure for `external_advisor_profile_requests`
-- Solicitud de registro de Asesor Externo (HU-011)
-- Nota: el solicitante aún NO existe en sis_user/sis_login
-- Documentos almacenados en BLOB
-- ----------------------------
DROP TABLE IF EXISTS `external_advisor_profile_requests`;
CREATE TABLE `external_advisor_profile_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `applicant_id` varchar(50) NOT NULL COMMENT 'Cédula / identificador del solicitante (futuro sis_login.id)',
  `full_name` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `telefono` varchar(15) DEFAULT NULL,
  `id_tipo_tel` varchar(1) DEFAULT NULL,
  `institution` varchar(255) NOT NULL,
  `specialization` varchar(255) NOT NULL,

  `cv_document` longblob NOT NULL,
  `cv_file_name` varchar(255) NOT NULL,
  `cv_mime_type` varchar(100) NOT NULL,
  `cv_file_size` int(11) NOT NULL,

  `id_copy_document` longblob NOT NULL,
  `id_copy_file_name` varchar(255) NOT NULL,
  `id_copy_mime_type` varchar(100) NOT NULL,
  `id_copy_file_size` int(11) NOT NULL,

  `status` enum('En Revision','Aprobado','Rechazado') NOT NULL DEFAULT 'En Revision',
  `rejection_count` int(11) DEFAULT 0 COMMENT 'Contador de rechazos (máximo 2 antes de bloqueo)',
  `approval_expires_at` datetime DEFAULT NULL COMMENT 'Fecha de vencimiento de la aprobación',
  `linked_proposal_id` int(11) DEFAULT NULL COMMENT 'FK a tfg_proposals (propuesta vinculada)',
  `linked_student_id` varchar(50) DEFAULT NULL COMMENT 'ID del estudiante a asesorar',
  `admin_comments` text DEFAULT NULL,
  `reviewed_by` varchar(50) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_external_advisor_request_applicant` (`applicant_id`),
  KEY `idx_external_advisor_request_status` (`status`),
  KEY `idx_approval_expires` (`approval_expires_at`),
  KEY `idx_linked_proposal` (`linked_proposal_id`),
  KEY `fk_external_advisor_request_reviewer` (`reviewed_by`),
  CONSTRAINT `fk_external_advisor_request_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `sis_user` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ----------------------------
-- HU-027: SISTEMA DE ARCHIVO HISTÓRICO
-- Tablas para archivar proyectos concluidos o cancelados
-- Cumple con Art. 68 RGPEA - Conservación de registros para auditoría
-- ----------------------------

-- ----------------------------
-- Table structure for `tfg_proposals_archive`
-- Almacena propuestas TFG archivadas con documentos comprimidos (GZIP)
-- ----------------------------
DROP TABLE IF EXISTS `tfg_proposals_archive`;
CREATE TABLE `tfg_proposals_archive` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `original_proposal_id` int(11) NOT NULL COMMENT 'ID original de la propuesta antes de archivar',
  `user_id` varchar(50) NOT NULL COMMENT 'ID del estudiante propietario',
  `title` varchar(255) NOT NULL COMMENT 'Título del TFG',
  `disciplines` varchar(255) DEFAULT NULL COMMENT 'Disciplinas del proyecto',
  `project_description` text DEFAULT NULL COMMENT 'Descripción del proyecto',
  `document` longblob DEFAULT NULL COMMENT 'Documento principal comprimido (GZIP)',
  `file_name` varchar(255) DEFAULT NULL COMMENT 'Nombre original del archivo',
  `mime_type` varchar(100) DEFAULT NULL COMMENT 'Tipo MIME del documento',
  `file_size` int(11) DEFAULT NULL COMMENT 'Tamaño original en bytes',
  `compressed_size` int(11) DEFAULT NULL COMMENT 'Tamaño comprimido en bytes',
  `is_compressed` tinyint(1) DEFAULT 1 COMMENT '1 si el documento está comprimido',
  `original_status` varchar(50) DEFAULT NULL COMMENT 'Estado que tenía la propuesta antes de archivar',
  `archive_reason` enum('Concluido','Cancelado') NOT NULL COMMENT 'Razón del archivado',
  `admin_comments` text DEFAULT NULL COMMENT 'Comentarios del revisor',
  `reviewed_by` varchar(50) DEFAULT NULL COMMENT 'ID del último revisor',
  `reviewed_at` datetime DEFAULT NULL COMMENT 'Fecha de última revisión',
  `original_created_at` datetime DEFAULT NULL COMMENT 'Fecha de creación original',
  `original_updated_at` datetime DEFAULT NULL COMMENT 'Fecha de última actualización original',
  `archived_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de archivado',
  `archived_by` varchar(50) DEFAULT NULL COMMENT 'Usuario que archivó (NULL si automático)',
  PRIMARY KEY (`id`),
  KEY `idx_original_proposal` (`original_proposal_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_archive_reason` (`archive_reason`),
  KEY `idx_archived_at` (`archived_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table structure for `registered_projects_archive`
-- Almacena proyectos registrados archivados
-- ----------------------------
DROP TABLE IF EXISTS `registered_projects_archive`;
CREATE TABLE `registered_projects_archive` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `original_project_id` int(11) NOT NULL COMMENT 'ID original del proyecto',
  `original_proposal_id` int(11) NOT NULL COMMENT 'ID de la propuesta asociada',
  `project_type_id` int(11) DEFAULT NULL COMMENT 'Tipo de proyecto',
  `project_type_name` varchar(100) DEFAULT NULL COMMENT 'Nombre del tipo (snapshot)',
  `original_status` varchar(50) DEFAULT NULL COMMENT 'Estado antes de archivar',
  `archive_reason` enum('Concluido','Cancelado') NOT NULL,
  `start_date` date DEFAULT NULL COMMENT 'Fecha de inicio del proyecto',
  `end_date` date DEFAULT NULL COMMENT 'Fecha de finalización',
  `final_grade` decimal(5,2) DEFAULT NULL COMMENT 'Calificación final (si aplica)',
  `supervisor_id` varchar(50) DEFAULT NULL COMMENT 'ID del supervisor',
  `supervisor_name` varchar(255) DEFAULT NULL COMMENT 'Nombre del supervisor (snapshot)',
  `original_created_at` datetime DEFAULT NULL,
  `original_updated_at` datetime DEFAULT NULL,
  `archived_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_original_project` (`original_project_id`),
  KEY `idx_original_proposal` (`original_proposal_id`),
  KEY `idx_archive_reason` (`archive_reason`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table structure for `project_members_archive`
-- Almacena miembros de proyectos archivados (snapshot)
-- ----------------------------
DROP TABLE IF EXISTS `project_members_archive`;
CREATE TABLE `project_members_archive` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `original_member_id` int(11) NOT NULL COMMENT 'ID original del registro de miembro',
  `original_project_id` int(11) NOT NULL COMMENT 'ID original del proyecto',
  `user_id` varchar(50) NOT NULL COMMENT 'ID del miembro',
  `user_name` varchar(255) DEFAULT NULL COMMENT 'Nombre completo (snapshot)',
  `role` varchar(50) DEFAULT NULL COMMENT 'Rol en el proyecto (Líder, Miembro)',
  `member_status` varchar(50) DEFAULT NULL COMMENT 'Estado del miembro',
  `joined_at` datetime DEFAULT NULL COMMENT 'Fecha de incorporación',
  `left_at` datetime DEFAULT NULL COMMENT 'Fecha de salida (si aplica)',
  `archived_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_original_project` (`original_project_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table structure for `tfg_files_archive`
-- Almacena archivos adicionales archivados y comprimidos
-- ----------------------------
DROP TABLE IF EXISTS `tfg_files_archive`;
CREATE TABLE `tfg_files_archive` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `original_file_id` int(11) NOT NULL COMMENT 'ID original del archivo',
  `original_proposal_id` int(11) NOT NULL COMMENT 'ID de la propuesta asociada',
  `file_name` varchar(255) NOT NULL COMMENT 'Nombre original del archivo',
  `mime_type` varchar(100) DEFAULT NULL,
  `original_size` int(11) DEFAULT NULL COMMENT 'Tamaño original en bytes',
  `compressed_size` int(11) DEFAULT NULL COMMENT 'Tamaño comprimido',
  `file_data` longblob DEFAULT NULL COMMENT 'Contenido comprimido (GZIP)',
  `is_compressed` tinyint(1) DEFAULT 1,
  `document_type` varchar(100) DEFAULT NULL COMMENT 'Tipo de documento',
  `uploaded_by` varchar(50) DEFAULT NULL,
  `original_created_at` datetime DEFAULT NULL,
  `archived_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_original_file` (`original_file_id`),
  KEY `idx_original_proposal` (`original_proposal_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table structure for `archive_audit_log`
-- Registra todos los accesos al archivo histórico (Art. 68 RGPEA)
-- ----------------------------
DROP TABLE IF EXISTS `archive_audit_log`;
CREATE TABLE `archive_audit_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(50) NOT NULL COMMENT 'Usuario que accede',
  `action_type` enum('VIEW','DOWNLOAD','SEARCH') NOT NULL,
  `archived_proposal_id` int(11) DEFAULT NULL COMMENT 'Propuesta archivada accedida',
  `archived_project_id` int(11) DEFAULT NULL COMMENT 'Proyecto archivado accedido',
  `details` text DEFAULT NULL COMMENT 'Detalles adicionales (filtros, etc.)',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP del cliente',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action_type`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='Log de auditoría para accesos al archivo histórico (Art. 68 RGPEA)';

-- ----------------------------
-- HU-037: Table structure for `user_alerts`
-- Sistema de alertas/notificaciones internas
-- ----------------------------
DROP TABLE IF EXISTS `user_alerts`;
CREATE TABLE `user_alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(50) NOT NULL COMMENT 'Usuario destinatario de la alerta',
  `subject` varchar(255) NOT NULL COMMENT 'Asunto de la alerta',
  `message` text NOT NULL COMMENT 'Mensaje detallado',
  `alert_type` enum('Nueva Propuesta','Propuesta Aprobada','Propuesta Rechazada','Documento Final','Correccion Solicitada','Asesor Aprobado','Asesor Rechazado','Prorroga','Informativa','Sistema') NOT NULL DEFAULT 'Informativa' COMMENT 'Tipo de alerta',
  `priority` enum('Alta','Media','Baja') DEFAULT 'Media' COMMENT 'Prioridad de la alerta',
  `related_entity_type` varchar(50) DEFAULT NULL COMMENT 'Tipo de entidad relacionada (proposal, document, etc.)',
  `related_entity_id` int(11) DEFAULT NULL COMMENT 'ID de la entidad relacionada',
  `read_at` datetime DEFAULT NULL COMMENT 'Fecha/hora en que se leyó la alerta',
  `sent_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha/hora de envío',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_alert_type` (`alert_type`),
  KEY `idx_priority` (`priority`),
  KEY `idx_read_at` (`read_at`),
  KEY `idx_sent_at` (`sent_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='HU-037: Alertas internas del sistema';

-- ----------------------------
-- HU-011: Table structure for `external_advisor_linked_students`
-- Vinculación de asesor externo con todos los estudiantes de un grupo TFG
-- ----------------------------
DROP TABLE IF EXISTS `external_advisor_linked_students`;
CREATE TABLE `external_advisor_linked_students` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `advisor_request_id` int(11) DEFAULT NULL COMMENT 'FK a external_advisor_profile_requests (asesores externos; NULL para asesores internos)',
    `internal_advisor_id` varchar(50) DEFAULT NULL COMMENT 'Cédula del asesor interno de comité (FK a sis_user; NULL para asesores externos)',
    `student_id` varchar(50) NOT NULL COMMENT 'ID del estudiante vinculado (FK a sis_user)',
    `is_primary` tinyint(1) DEFAULT 0 COMMENT '1 si es el estudiante principal (seleccionado en registro)',
    `project_id` int(11) DEFAULT NULL COMMENT 'FK a registered_projects (proyecto del grupo)',
    `linked_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de vinculación',
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_advisor_student` (`advisor_request_id`, `student_id`),
    UNIQUE KEY `unique_internal_advisor_student` (`internal_advisor_id`, `student_id`),
    KEY `idx_advisor_request` (`advisor_request_id`),
    KEY `idx_internal_advisor` (`internal_advisor_id`),
    KEY `idx_student` (`student_id`),
    KEY `idx_project` (`project_id`),
    KEY `idx_eal_lookup` (`advisor_request_id`, `student_id`, `is_primary`),
    CONSTRAINT `fk_eal_advisor_request` FOREIGN KEY (`advisor_request_id`) 
        REFERENCES `external_advisor_profile_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_eal_internal_advisor` FOREIGN KEY (`internal_advisor_id`)
        REFERENCES `sis_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_eal_student` FOREIGN KEY (`student_id`) 
        REFERENCES `sis_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci 
COMMENT='HU-011: Vinculación de asesor (externo o interno de comité) con los estudiantes de un grupo TFG';

-- Datos semilla: vincular miembros del comité #9 (asesores internos) con los estudiantes del proyecto 2024
-- tutor=800870458 (Darinka Grbic, rol 5), asesor_1=503020651 (Carlos Chanto, rol 3), asesor_2=503230754 (Eddier López, rol 3)
INSERT INTO external_advisor_linked_students (internal_advisor_id, student_id, is_primary, project_id) VALUES
('800870458', '504410118', 1, @seed_registered_project_2024_id),
('800870458', '504430777', 0, @seed_registered_project_2024_id),
('800870458', '118440202', 0, @seed_registered_project_2024_id),
('503020651', '504410118', 0, @seed_registered_project_2024_id),
('503020651', '504430777', 0, @seed_registered_project_2024_id),
('503020651', '118440202', 0, @seed_registered_project_2024_id),
('503230754', '504410118', 0, @seed_registered_project_2024_id),
('503230754', '504430777', 0, @seed_registered_project_2024_id),
('503230754', '118440202', 0, @seed_registered_project_2024_id);

-- ----------------------------
-- HU-029: Table structure for `chat_conversations`
-- Sistema de comunicación interna (Chat tipo Teams)
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
-- HU-029: Table structure for `chat_participants`
-- Participantes de cada conversación
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
-- HU-029: Table structure for `chat_messages`
-- Mensajes del sistema de chat
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
