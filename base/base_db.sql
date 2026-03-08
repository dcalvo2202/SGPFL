-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 09-03-2026 a las 00:06:43
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `base_db`
--

DELIMITER $$
--
-- Procedimientos
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `delete_mod` (IN `p_id_mod` INT, OUT `respuesta` INT)   BEGIN







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







END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `delete_roll` (IN `p_id_roll` INT)   BEGIN







CALL  delete_roll_permits(p_id_roll);



DELETE FROM sis_rolls



WHERE id_roll=p_id_roll;







END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `delete_roll_permits` (IN `p_id_roll` INT)   BEGIN







DELETE FROM sis_permits



WHERE id_roll=p_id_roll;







END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `delete_user` (IN `p_id` VARCHAR(50), OUT `res` TINYINT UNSIGNED)   BEGIN
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
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `exploit` (INOUT `pcadena` VARCHAR(5000), IN `separador` VARCHAR(1), OUT `vtexto` VARCHAR(5000))   BEGIN



set vtexto = substring(pcadena, 1, instr(pcadena, separador)-1);



set pcadena = substring(pcadena, instr(pcadena, separador)+1);



END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `insert_log` (IN `p_id_user` INT, IN `p_detail` BLOB, OUT `res` TINYINT)   BEGIN
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
		INSERT INTO `sis_log`(id_user,date_bi,detail) VALUES (p_id_user,now(),p_detail);
	COMMIT;
	
	-- SUCCESS
	SET res = 0;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `insert_mod` (IN `p_name_mod` VARCHAR(100), IN `p_desc_mod` VARCHAR(500), OUT `respuesta` INT)   BEGIN







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







END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `insert_roll_permits` (IN `p_id_roll` INT, IN `p_cadena` VARCHAR(500))   BEGIN



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



END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `insert_user` (IN `p_id` VARCHAR(50), IN `p_nombre` VARCHAR(150), IN `p_email` VARCHAR(100), IN `p_telefono` VARCHAR(15), IN `p_id_tipo_tel` VARCHAR(1), IN `p_id_roll` INT, IN `p_pass` VARCHAR(255), OUT `res` TINYINT UNSIGNED)   BEGIN
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
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `update_mod` (IN `p_id_mod` INT, IN `p_mod_name` VARCHAR(100), IN `p_mod_desc` VARCHAR(500), OUT `respuesta` INT)   BEGIN







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







END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `update_perfil` (IN `p_id` VARCHAR(50), IN `p_email` VARCHAR(100), IN `p_telefono` VARCHAR(15), IN `p_id_tipo_tel` VARCHAR(1), IN `p_pass` VARCHAR(255), OUT `res` TINYINT UNSIGNED)   BEGIN
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
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `update_roll` (IN `p_id_roll` INT, IN `p_roll_name` VARCHAR(100), IN `p_roll_desc` VARCHAR(500), OUT `respuesta` INT)   BEGIN







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







END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `update_user` (IN `p_id` VARCHAR(50), IN `p_nombre` VARCHAR(150), IN `p_email` VARCHAR(100), IN `p_telefono` VARCHAR(15), IN `p_id_tipo_tel` VARCHAR(1), IN `p_id_roll` INT, OUT `res` TINYINT UNSIGNED)   BEGIN
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
END$$

--
-- Funciones
--
CREATE DEFINER=`root`@`localhost` FUNCTION `checklogin` (`p_id` VARCHAR(50), `p_pass` VARCHAR(255)) RETURNS INT(1)  BEGIN
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
END$$

CREATE DEFINER=`root`@`localhost` FUNCTION `check_permits` (`p_id_mod` INT, `p_id_action` INT, `p_id_roll` INT) RETURNS INT(1)  BEGIN

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

END$$

CREATE DEFINER=`root`@`localhost` FUNCTION `insert_roll` (`p_roll_name` VARCHAR(100), `p_roll_desc` VARCHAR(500)) RETURNS INT(11)  BEGIN



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



END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `acuerdo_cancelacion`
--

CREATE TABLE `acuerdo_cancelacion` (
  `id` int(11) NOT NULL,
  `proyecto_id` int(11) NOT NULL,
  `usuario_id` varchar(50) NOT NULL,
  `motivo` varchar(500) NOT NULL,
  `observaciones` text DEFAULT NULL,
  `fecha_cancelacion` datetime NOT NULL,
  `fecha_ultimo_avance_usada` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `archive_audit_log`
--

CREATE TABLE `archive_audit_log` (
  `id` int(11) NOT NULL,
  `user_id` varchar(50) NOT NULL COMMENT 'Usuario que accede',
  `action_type` enum('VIEW','DOWNLOAD','SEARCH') NOT NULL,
  `archived_proposal_id` int(11) DEFAULT NULL COMMENT 'Propuesta archivada accedida',
  `archived_project_id` int(11) DEFAULT NULL COMMENT 'Proyecto archivado accedido',
  `details` text DEFAULT NULL COMMENT 'Detalles adicionales (filtros, etc.)',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP del cliente',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='Log de auditoría para accesos al archivo histórico (Art. 68 RGPEA)';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `idCategoria` int(20) NOT NULL,
  `nombre` varchar(20) NOT NULL,
  `categoria` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`idCategoria`, `nombre`, `categoria`) VALUES
(1, 'categoria1', 0),
(2, 'categoria 2', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comite`
--

CREATE TABLE `comite` (
  `Id` int(11) NOT NULL,
  `tutor` varchar(50) NOT NULL,
  `asesor_1` varchar(50) NOT NULL,
  `asesor_2` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Volcado de datos para la tabla `comite`
--

INSERT INTO `comite` (`Id`, `tutor`, `asesor_1`, `asesor_2`) VALUES
(7, '105710421', '107010122', '110600492'),
(8, '116440018', '205610158', '206580363');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `external_advisor_linked_students`
--

CREATE TABLE `external_advisor_linked_students` (
  `id` int(11) NOT NULL,
  `advisor_request_id` int(11) NOT NULL COMMENT 'FK a external_advisor_profile_requests',
  `student_id` varchar(50) NOT NULL COMMENT 'ID del estudiante vinculado (FK a sis_user)',
  `is_primary` tinyint(1) DEFAULT 0 COMMENT '1 si es el estudiante principal (seleccionado en registro)',
  `project_id` int(11) DEFAULT NULL COMMENT 'FK a registered_projects (proyecto del grupo)',
  `linked_at` datetime DEFAULT current_timestamp() COMMENT 'Fecha de vinculación'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='HU-011: Vinculación de asesor externo con todos los estudiantes de un grupo TFG';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `external_advisor_profile_requests`
--

CREATE TABLE `external_advisor_profile_requests` (
  `id` int(11) NOT NULL,
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
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `project_history`
--

CREATE TABLE `project_history` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `action_type` enum('Creado','Modificado','Miembro Agregado','Miembro Removido','Estado Cambiado') NOT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `project_members`
--

CREATE TABLE `project_members` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `role` enum('Líder','Miembro') NOT NULL,
  `status` enum('Activo','Inactivo','Retirado') DEFAULT 'Activo',
  `joined_at` datetime DEFAULT current_timestamp(),
  `left_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `project_members_archive`
--

CREATE TABLE `project_members_archive` (
  `id` int(11) NOT NULL,
  `original_member_id` int(11) NOT NULL COMMENT 'ID original del registro de miembro',
  `original_project_id` int(11) NOT NULL COMMENT 'ID original del proyecto',
  `user_id` varchar(50) NOT NULL COMMENT 'ID del miembro',
  `user_name` varchar(255) DEFAULT NULL COMMENT 'Nombre completo (snapshot)',
  `role` varchar(50) DEFAULT NULL COMMENT 'Rol en el proyecto (Líder, Miembro)',
  `member_status` varchar(50) DEFAULT NULL COMMENT 'Estado del miembro',
  `joined_at` datetime DEFAULT NULL COMMENT 'Fecha de incorporación',
  `left_at` datetime DEFAULT NULL COMMENT 'Fecha de salida (si aplica)',
  `archived_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `project_types`
--

CREATE TABLE `project_types` (
  `id` int(11) NOT NULL,
  `type_name` varchar(100) NOT NULL,
  `max_members` int(11) NOT NULL DEFAULT 1,
  `description` text DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Volcado de datos para la tabla `project_types`
--

INSERT INTO `project_types` (`id`, `type_name`, `max_members`, `description`, `active`, `created_at`) VALUES
(1, 'Tesis', 2, 'Trabajo individual o en parejas', 1, '2026-03-08 12:45:25'),
(2, 'Proyecto de Graduación', 3, 'Proyecto grupal de hasta 3 integrantes', 1, '2026-03-08 12:45:25'),
(3, 'Seminario', 8, 'Trabajo grupal de hasta 8 integrantes', 1, '2026-03-08 12:45:25');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proyecto_aprobado`
--

CREATE TABLE `proyecto_aprobado` (
  `id_aprobado` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `proposal_id` int(11) DEFAULT NULL,
  `comite_id` int(11) NOT NULL,
  `documento` longblob NOT NULL,
  `aprobado` tinyint(1) NOT NULL DEFAULT 1,
  `identificador` varchar(50) NOT NULL,
  `fecha_creacion` datetime NOT NULL,
  `fecha_finalizacion` datetime NOT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'ACTIVO',
  `fecha_ultimo_avance` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proyecto_aprobado_estudiantes`
--

CREATE TABLE `proyecto_aprobado_estudiantes` (
  `id_aprobado` int(11) NOT NULL,
  `estudiante_id` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proyecto_notas`
--

CREATE TABLE `proyecto_notas` (
  `id_nota` int(11) NOT NULL,
  `proyecto_id` int(11) NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `notas` text NOT NULL,
  `creado_por` varchar(50) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `etapa_proyecto` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `registered_projects`
--

CREATE TABLE `registered_projects` (
  `id` int(11) NOT NULL,
  `tfg_proposal_id` int(11) NOT NULL,
  `project_type_id` int(11) NOT NULL,
  `status` enum('Registrado','En Desarrollo','En Revisión','Finalizado','Aprobado','Rechazado') DEFAULT 'Registrado',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `final_grade` decimal(3,1) DEFAULT NULL,
  `supervisor_id` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `registered_projects_archive`
--

CREATE TABLE `registered_projects_archive` (
  `id` int(11) NOT NULL,
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
  `archived_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sis_log`
--

CREATE TABLE `sis_log` (
  `id_bi` int(11) NOT NULL COMMENT 'Identificador para bitacora',
  `id_user` varchar(20) DEFAULT NULL,
  `date_bi` datetime DEFAULT NULL,
  `detail` blob DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sis_login`
--

CREATE TABLE `sis_login` (
  `id` varchar(50) NOT NULL,
  `pass` varchar(255) NOT NULL,
  `id_roll` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Volcado de datos para la tabla `sis_login`
--

INSERT INTO `sis_login` (`id`, `pass`, `id_roll`) VALUES
('105710421', '5d7845ac6ee7cfffafc5fe5f35cf666d', 5),
('107010122', '5d7845ac6ee7cfffafc5fe5f35cf666d', 5),
('110600492', '$2y$10$qWLc.SM3Vv5i4v9WkJJeOOGqfXeiPGepjCJ3EdGe.LZPX1rQ3rdBq', 3),
('111710169', '5d7845ac6ee7cfffafc5fe5f35cf666d', 2),
('116440018', '5d7845ac6ee7cfffafc5fe5f35cf666d', 4),
('118440202', '$2y$10$DI4WYukEr9QIoXTpJGx8ku41/63fH0K/qKjRVeZOIE9KZhAWFXiZO', 4),
('205610158', '5d7845ac6ee7cfffafc5fe5f35cf666d', 1),
('205830110', '5d7845ac6ee7cfffafc5fe5f35cf666d', 5),
('206580363', '5d7845ac6ee7cfffafc5fe5f35cf666d', 4),
('402290345', '5d7845ac6ee7cfffafc5fe5f35cf666d', 4),
('503020651', '5d7845ac6ee7cfffafc5fe5f35cf666d', 3),
('503230754', '5d7845ac6ee7cfffafc5fe5f35cf666d', 3),
('503550224', '5d7845ac6ee7cfffafc5fe5f35cf666d', 4),
('504410118', '5d7845ac6ee7cfffafc5fe5f35cf666d', 4),
('504430777', '5d7845ac6ee7cfffafc5fe5f35cf666d', 4),
('701810347', '5d7845ac6ee7cfffafc5fe5f35cf666d', 5),
('800810596', '5d7845ac6ee7cfffafc5fe5f35cf666d', 2),
('800870458', '5d7845ac6ee7cfffafc5fe5f35cf666d', 5);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sis_mod`
--

CREATE TABLE `sis_mod` (
  `id_mod` int(11) NOT NULL,
  `mod_name` varchar(100) DEFAULT NULL,
  `mod_desc` varchar(500) DEFAULT NULL,
  `active` varchar(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Volcado de datos para la tabla `sis_mod`
--

INSERT INTO `sis_mod` (`id_mod`, `mod_name`, `mod_desc`, `active`) VALUES
(1, 'Acceso', 'Modulo de acceso al sistema', '1'),
(2, 'Busqueda', 'Modulo de busqueda de proyectos', '1'),
(3, 'Historial y Auditoria', 'Modulo de historial y auditoria de los documentos', '1'),
(4, 'Notificaciones', 'Modulo de notificaciones para el sistema', '1'),
(5, 'Documentacion y versionado', 'Modulo para la documentacion y versionado de los documentos', '1'),
(6, 'Gestion de proyectos', 'Modulo para la gestion de los proyectos', '1'),
(7, 'Reportes y paneles', 'Modulo que permite el acceso a reportes y paneles', '1'),
(8, 'Gestion academica', 'Modulo para la gestion academica del sistema', '1');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sis_mod_actions`
--

CREATE TABLE `sis_mod_actions` (
  `id_action` int(11) NOT NULL,
  `action_name` varchar(100) DEFAULT NULL,
  `action_desc` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Volcado de datos para la tabla `sis_mod_actions`
--

INSERT INTO `sis_mod_actions` (`id_action`, `action_name`, `action_desc`) VALUES
(1, 'Ver', 'Ver elementos del modulo'),
(2, 'Listar', 'Listar elementos del modulo'),
(3, 'Añadir', 'Añade elementos del modulo'),
(4, 'Editar', 'Modifica elementos del modulo'),
(5, 'Eliminar', 'Elimina elementos del modulo'),
(6, 'Imprimir', 'Permite imprimir elementos del modulo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sis_parametros_varios`
--

CREATE TABLE `sis_parametros_varios` (
  `id_pv` int(16) NOT NULL,
  `parametro` varchar(50) DEFAULT NULL COMMENT 'Nombre del parametro',
  `valor` varchar(100) DEFAULT NULL COMMENT 'Valor del parametro',
  `descripcion` varchar(300) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='Tabla para almacenar parametros varios';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sis_permits`
--

CREATE TABLE `sis_permits` (
  `id_permit` int(11) NOT NULL,
  `id_mod` int(11) NOT NULL,
  `id_action` int(11) NOT NULL,
  `id_roll` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Volcado de datos para la tabla `sis_permits`
--

INSERT INTO `sis_permits` (`id_permit`, `id_mod`, `id_action`, `id_roll`) VALUES
(19, 1, 1, 1),
(20, 1, 2, 1),
(21, 1, 3, 1),
(22, 1, 4, 1),
(23, 1, 5, 1),
(24, 1, 6, 1),
(25, 2, 1, 1),
(26, 2, 2, 1),
(27, 2, 3, 1),
(28, 2, 4, 1),
(29, 2, 5, 1),
(30, 2, 6, 1),
(31, 3, 1, 1),
(32, 3, 2, 1),
(33, 3, 3, 1),
(34, 3, 4, 1),
(35, 3, 5, 1),
(36, 3, 6, 1),
(37, 4, 1, 1),
(38, 4, 2, 1),
(39, 4, 3, 1),
(40, 4, 4, 1),
(41, 4, 5, 1),
(42, 4, 6, 1),
(43, 5, 1, 1),
(44, 5, 2, 1),
(45, 5, 3, 1),
(46, 5, 4, 1),
(47, 5, 5, 1),
(48, 5, 6, 1),
(49, 6, 1, 1),
(50, 6, 2, 1),
(51, 6, 3, 1),
(52, 6, 4, 1),
(53, 6, 5, 1),
(54, 6, 6, 1),
(55, 7, 1, 1),
(56, 7, 2, 1),
(57, 7, 3, 1),
(58, 7, 4, 1),
(59, 7, 5, 1),
(60, 7, 6, 1),
(61, 8, 1, 1),
(62, 8, 2, 1),
(63, 8, 3, 1),
(64, 8, 4, 1),
(65, 8, 5, 1),
(66, 8, 6, 1),
(67, 1, 1, 2),
(68, 1, 2, 2),
(69, 1, 6, 2),
(70, 2, 1, 2),
(71, 2, 2, 2),
(72, 2, 3, 2),
(73, 2, 4, 2),
(74, 2, 5, 2),
(75, 2, 6, 2),
(76, 3, 1, 2),
(77, 3, 2, 2),
(78, 3, 3, 2),
(79, 3, 4, 2),
(80, 3, 5, 2),
(81, 3, 6, 2),
(82, 4, 1, 2),
(83, 4, 2, 2),
(84, 4, 3, 2),
(85, 4, 4, 2),
(86, 4, 5, 2),
(87, 4, 6, 2),
(88, 5, 1, 2),
(89, 5, 2, 2),
(90, 5, 3, 2),
(91, 5, 4, 2),
(92, 5, 5, 2),
(93, 5, 6, 2),
(94, 6, 1, 2),
(95, 6, 2, 2),
(96, 6, 3, 2),
(97, 6, 4, 2),
(98, 6, 5, 2),
(99, 6, 6, 2),
(100, 7, 1, 2),
(101, 7, 2, 2),
(102, 7, 3, 2),
(103, 7, 4, 2),
(104, 7, 5, 2),
(105, 7, 6, 2),
(106, 8, 1, 2),
(107, 8, 2, 2),
(108, 8, 3, 2),
(109, 8, 4, 2),
(110, 8, 5, 2),
(111, 8, 6, 2),
(112, 1, 1, 3),
(113, 1, 3, 3),
(114, 2, 1, 3),
(115, 2, 2, 3),
(116, 2, 4, 3),
(117, 2, 5, 3),
(118, 2, 6, 3),
(119, 3, 1, 3),
(120, 3, 2, 3),
(121, 3, 3, 3),
(122, 3, 4, 3),
(123, 3, 5, 3),
(124, 3, 6, 3),
(125, 4, 1, 3),
(126, 4, 2, 3),
(127, 4, 3, 3),
(128, 4, 4, 3),
(129, 4, 5, 3),
(130, 4, 6, 3),
(131, 5, 1, 3),
(132, 5, 2, 3),
(133, 5, 3, 3),
(134, 5, 4, 3),
(135, 5, 5, 3),
(136, 5, 6, 3),
(137, 6, 1, 3),
(138, 6, 2, 3),
(139, 6, 3, 3),
(140, 6, 4, 3),
(141, 6, 5, 3),
(142, 6, 6, 3),
(143, 7, 1, 3),
(144, 7, 2, 3),
(145, 7, 6, 3),
(146, 8, 1, 3),
(147, 8, 2, 3),
(148, 8, 3, 3),
(149, 8, 4, 3),
(150, 8, 5, 3),
(151, 8, 6, 3),
(152, 1, 1, 4),
(153, 3, 1, 4),
(154, 3, 2, 4),
(155, 3, 6, 4),
(156, 4, 1, 4),
(157, 4, 2, 4),
(158, 4, 6, 4),
(159, 5, 1, 4),
(160, 5, 2, 4),
(161, 5, 3, 4),
(162, 5, 4, 4),
(163, 5, 5, 4),
(164, 5, 6, 4),
(165, 6, 1, 4),
(166, 6, 3, 4),
(167, 6, 4, 4),
(168, 6, 6, 4),
(169, 7, 1, 4),
(170, 7, 6, 4),
(171, 8, 1, 4),
(172, 8, 3, 4),
(173, 8, 4, 4),
(174, 1, 1, 5),
(175, 2, 1, 5),
(176, 3, 1, 5),
(177, 3, 2, 5),
(178, 4, 1, 5),
(179, 4, 2, 5),
(180, 4, 6, 5),
(181, 5, 1, 5),
(182, 6, 1, 5),
(183, 6, 3, 5),
(184, 6, 4, 5),
(185, 6, 6, 5),
(186, 7, 1, 5),
(187, 7, 6, 5),
(188, 8, 1, 5);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sis_rolls`
--

CREATE TABLE `sis_rolls` (
  `id_roll` int(11) NOT NULL,
  `roll_name` varchar(100) DEFAULT NULL,
  `roll_desc` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Volcado de datos para la tabla `sis_rolls`
--

INSERT INTO `sis_rolls` (`id_roll`, `roll_name`, `roll_desc`) VALUES
(1, 'Administrador', 'Permisos totales sobre todos los modulos este usuario no encuentra ninguna restricción'),
(2, 'Gestor Academico', 'Tiene todos los permisos, excepto los relacionados al control de modulos y permisos de usuarios'),
(3, 'CTFG', 'Comisión de trabajos finales de graduación, este tiene permisos de lectura sobre los documentos de los estudiantes y puede aprobar o rechazar los trabajos finales de graduación'),
(4, 'Estudiante', 'Estudiante de la universidad, este tiene permisos de lectura, escritura sobre sus documentos y puede enviar solicitudes de trabajos finales de graduación'),
(5, 'Asesor', 'Tiene acceso de lectura a los modulos del estudiante');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sis_sessions`
--

CREATE TABLE `sis_sessions` (
  `sid` varchar(100) NOT NULL DEFAULT '',
  `expires` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `forced_expires` int(11) UNSIGNED NOT NULL,
  `ua` varchar(40) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `sis_sessions`
--

INSERT INTO `sis_sessions` (`sid`, `expires`, `forced_expires`, `ua`) VALUES
('0k33888pppuuugg000xxxhhhrroooaaa', 1502488045, 1502489843, '08a5b82c39119cda924c9ad777dbe60f9e545f3a'),
('1zzz333888lll111ttiiiimmmnnnfffp', 1496522231, 1496523862, '10199e19da728fd0b39a0d684444e7517b55a611'),
('2eeeaabbbvvvll111777oooaaabbvvvs', 1502488048, 1502489847, '08a5b82c39119cda924c9ad777dbe60f9e545f3a'),
('2u8n59po84g2vvhopcg1evnr8g', 1773003947, 1773005747, '3ad4dc606527fcfedeabd8e6714012ab5ffed8bd'),
('db999ll111666ddmmm333ffwwwyyyttt', 1502488050, 1502489848, '08a5b82c39119cda924c9ad777dbe60f9e545f3a'),
('qccxxxnnfffwwwqqggg000kkhhh77ooo', 1502488047, 1502489845, '08a5b82c39119cda924c9ad777dbe60f9e545f3a');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sis_sessions_vars`
--

CREATE TABLE `sis_sessions_vars` (
  `name` text NOT NULL,
  `value` text NOT NULL,
  `sid` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `sis_sessions_vars`
--

INSERT INTO `sis_sessions_vars` (`name`, `value`, `sid`) VALUES
('ðœ¨ÇªZ³VB!üÆ8þ®¨', 'Ï\rµ3\"¥¿¶AfÅˆ\në €…öÈ`zñ,)T6', '1zzz333888lll111ttiiiimmmnnnfffp'),
('…÷&þ¡½Ú™a w‡', 'qþjæÇèÐSß}ßgÇ§M™4*d/Ö„t¨|…ö¨òP', '1zzz333888lll111ttiiiimmmnnnfffp'),
('[ä ó¾éF¼¥[ò“M	-Œ', 'æ‘ÈB{¯8>ç¤£¿©äÂ', '1zzz333888lll111ttiiiimmmnnnfffp'),
('äúfæ\Z\0G´üùUB', '3N¿>S–Ï›¬Ö\Zù!šrê:-\'}[ûàïûÉõzw*', '1zzz333888lll111ttiiiimmmnnnfffp'),
('^Á3\0è×fø\0[.UN', '19Ö¬àø>%„%ÃzÃåüÎ', '1zzz333888lll111ttiiiimmmnnnfffp'),
('ˆe`òuéUfõêcrOú', '‹ ›.¦v§Q\n:Ø	Ê', '1zzz333888lll111ttiiiimmmnnnfffp'),
('ïíÓê1%ådþ½ù;¦', 'Q~‹((‘w’úyeÁ‘i»', '1zzz333888lll111ttiiiimmmnnnfffp'),
('#]N„S‰Ó\\	Ð» ×', '\rîM÷„ÅÒØpáªKÀåâÀÁíNb,0`R¶`òiÙ&4JC&Õ«Áxº,S5Ë›uˆi\n„HšNµˆMÍÙK‡*¶ŒFúïîâÿÍ’', '1zzz333888lll111ttiiiimmmnnnfffp'),
('ó}ß}ª“åHngåål', '¯\rÖþŽf¹¸¼4Ò¡Îs\"ÏwÚeµiÖ@0ú<õ°&<´zWì\'¤‚©\rxgÜ?MÓû…êœD„o3aÖÓð§–4.×NbSpÊû‰HfLËï}çLH?FAñ‹ëÞÇêïÅÚ@b\nëO–ˆzXN•—>nÀ\Zôòö„€TÌˆ)Ö¾¨áMlÖ;2mÍŠåóá60&»ï\'uŒò/Ìî¥]jRÓ„fñŠ|-·É0gòÔR¿Ô4ÀÅPçØh\rÁ‹¥‡³öL¿ ÕY–¡1N˜v­oŠóPÒ³8R_Œõ&3—qEÌË-¹5¸|˜-÷‰Ü?Iî °øXô™œ]kf ”nç›lp=UcºA,þÂ,ñíô‡©â^ie·²cõ2¼eú²ë!{è½ŒR”ô&úð-Å<¾ iŽö#d#âp¿„û(­J›­0úíªm-Lnðÿù^sŽrí¸ª\0Cë¤;s£öb]å¨ðÿ=/Ýuvƒ5¡µ¥){ç›d¼Q}OaÜAA³ï;úË-rÃ‘»aÎ_`Öù‹(ìMß<üU3«3yL~½¡ù&iûÐ…»RŠ™Üé-“ã;…úÿ}Ap…7Æ†·ÒIcwLç€47ÖS-¾€Š\Zº¨]Ó@\naù±éOlbzgÛæj³9KŸæ¦d¸6Là:ÛfgÁÉ1ë	å(×#+ÞúÁ11ö„€-\'ç˜÷.LãE\Z+	‹B„w!Ð0S9«*Îa™&ûC­x\"ªØ©P39=ŸaPæäŒæ~aª*Ÿa6—b½×k†;Ùi+ö\"0sõh?ÑáõÚJèˆ)óÓZ€ì[Øl¢ü–ÖtÅÆB™¯âŠmër¾°.‹:‰Hœêwü±øL«.(¾‘L”é\r ¥aà½¦Å´Ï+’-S’^Uot&.ä`p_+ÕVü©‰=7ÒË¬óH\rp1:„ þÌ<Úøc¶ªáðõSµ\ZÂÌÕ;FfÛ ­’4{?ÝÕÐÒ§weVò{4u¾Û£¼øz3ÃÞ„Žæƒ\\çdÛE^+ï¸{ýOí±<vmöÄÃ)’¢ó|pý´û…2gSV‹´5~%æM— šÃoò´éãeTr€Ï£ôÜ¯ª´†</Ð!\rãÊ€~‡õì”µÍ†’IdæT‹Š«ãUW	}_³9ðOèYªÜhÎŽ\nãi7ù<y\nùŽ\nØ±êùŽ¶³†úÍ`gi¶ƒû‹u‹[‘©º‹2X³<›¨¢Îa³OÞäOPÏSÐžû%™þfh…Œ	T0ÍÎêh«È—Ò˜ÌªpÄ£&9Rè\'g96; Œ{„‰/\0ÀHàáóÙ·ÄòTq\Z9Ñú7¡•—ùš5ìL{BÛ­6e:i¬ÿ5Œ/H3-]s¥vì/’\"êùˆå“cÁ7ô~Š¯Q$ËFÓpcköœ @F•à:LŒÎi0¦/‚0ä´ûz´}Û­ë5Å«3fÏ·«ù¬2: 4¦³õ³Kñ§­Ç¿Ò¾Ù‚Æ\\ÞÆ¾ò»:‹?X°¯ÈÒælÇl\Zîçþ\rl>#ûfŒ©Ï)Ÿ$pY:ó˜kLgQø1’ùÏ&ø\Z+Id‹[OE¢;}\'³¹Òì÷u\\¼ñÉhÇË4rý´3\0’„&“u©rhEÉœß#éE©zªFÂ6ÑŸŠ.b>@Ã¦åò`úå¦¼™\0£Õ½\Zw \\ª¸ÈQ{oCÉndvV¹¤ùhûu*Pâ¥uoWNè¦J‘àŠÌ\\™IL¼ÒM.A¤Xí¼ò…+zõ‰ŸÒp/}G2;¾\Z§^j/fEá?`	ùo:MÃ	›‹§tQ\'&K>#Ô”zPÎ˜½Ð\0\"ú\\u·þwÑæ..Œ]u u††©j=-tÖ\"u‚y\Z/ƒ‘—Pc‡»ÜÍ(‡eÓµ¾+xüañÐ|eLMw\'©µËçÏÉŸ4yV±/WïÙ-¡è\ZE“b#t½uUtóed]z|œXmwrŸª…ÁÉ|¡qòYÈZñ¯¾òE¦GäÁã„GÚyNŽb¹Íš&oÒ¯³±ÊÚmÈ UH|4ðŽ¢ôšlIQV-<B7À¸B\rÇÍÿPVE\'ÁsCæZØŽ\"ƒ’\rêØ^”i\rˆü¸±¤ÍöpÊX†fºÑ$Z2|•	‰~]Yûí,ó+äpúIà31´øŒ2Õmxq5“7²Gú­e`ÀxÛßÂå/Íä-O,êÐ\r>DÁ–Éùš4T´)_`÷ÿ½?62C«†{£°‰€œ6ªnðgÈ¥=µÖ:$×Ô/ó¯§»šj0arøÂõM\"|cÒVlBºœ\\Ì\0RÈé«ErF\rrÀ³üz”ýÄÙBQŸªC3…I’8è¾üÀÜŠŠÍÔÐêì×Ç5fÜª\rf¼P…»mC%eUÐŸŒÍø:…Ž/$ŒntÉt!gu¾257éÂ}@÷Ãƒ€\ZŠ\nþ31‚ò\n$e…†UmrÛ°þ>®ÓµIï_ q€Ë¿¸’¦Wwîü¶Ü«Óð ƒA‚@ Ú%V²æW­:Ø\'i‹øËù¸ÔmÖ!«£UÑi”Lø†6G\Z,èlƒÁø#µž×¦Ã–ï·\'Ð7cýà@÷’ü‡®{ÄÕù²Íuëo*óQÖÐ$ôÕ•àÌŽ¬`,”rp)G]EÈPýþu`ÇøCC5á©ý6•\nc•ÏUñ\"Žh7¨Qž°×Ä²ÖI›HªRWeÇ[ÿä/µè5‚ ôòÜ¸$Sôgi¯éLìÂ2Ty¸WAXî¹u\rö0´ï‘”áÛÈ\0p°=Mˆä\"-’ù¿¨\Z— ÏFÞ’\rÌ1krõŸ•ÿ|Ø,e­\\X­¡fèñð‡‡G÷ ã’ÃßÔ,!úL»q‰ªÖ“ibÂZëŸhÜÙæ×Tx-¤{GVtÈ~°få°À=O²ÿFM;GRì®6ž+Â™1wmTÍ¶±3«=R¿IaŽ>ÿFÚ–ª5rß³¨è•%o }â]ÌYÆSWìx*KW ¦ìßLa¶ z¶å!ºŠ2D²±¡…Y†p¶·U‡•;²¯#ã”Óx\rýÐZ)÷ñ^§ITO¼_A/Ý\ZýØœU­l#ÔßòËœ`x°ü6?odp\\dõJÓI´Ò— IôŠ¶4J;Íz||›$oº:pVi³\"é{¾q‰áÚq–ËMoÆ“¡ê¨tÊãrÈbd®ð?Óp\r<#|sVß0í4%úf·ëXšçßAŒTññÚŸì)þeô\"„Ù\ZÁ|ª‡]rd<hŸNÄD5çZ²ÑÃa%a²ÙlþÊ—[pÓ\'I‹B¶X‰„ûf¬~l6Óí‚G ðèV™-#ÜA,–×l±’¾Ðô¸\\â0»Uõ1%ólÆû·x\Z®èÎŒZÕ¥fÓ§Äim«Ð$Rùï¼`e[ë—fMímU‘#GýöEªw”K\"\nöŸÕ$2ìÊåÉýë—’¿oñ‘Qi‘¾+ÀÙç—sZ#£¶Œà7 s‰ðèë‰óy_7™ÐDÈÁ|\\÷â³»$êê@ÜÐw˜•OÆ¶Å›æh¥j»â²4¸³À;Õò;9$\r#œxÄ˜Aò•úªkâ2o~½Ôšî¤Ðµ+îá•–nz‡/þÝ³]\rïKŠÝ/\r•t!füuÒÔÿí¦èÓUD>¹9’,k}-ã>Ž0š¿Hgçˆ™nÉòÿòë1.ÕtðXggÁ‹ >7¿Ö/mF›B£¹³ywúÑŒa°‰ØI€‰OAöÚ~~¦éH7¾’¹Yã|•ëËwßC‡P~™&þbü\Z\\ ÃˆPÌaQÆINÅÎ-*b-ß=ß¢FÐ°;!ÑÌäŸÈ…€6‰\ZÖ‰?«ëcG“ÿÞ~t˜l;Ëg%¯H7ˆî”ÿu–Ã^ñ–´B\r5PsKŸS¡\nÍ’VWpŸðoIbjèòå1­ëö‹Æí?VoI\0E‘]—[vÃu°ó¯u¾õmÎfå!|¤žä\\lRÏGO‚K1½¦)~ýÌ\rK$•ÜòÄÅÕÏ–¦7oÒ	A°DZ	2KÌSx\"¬¬kto2{„3üÌ.‰|/úðæÅf9‚ç±þ‹¨†þ­§éºÜn9ò‹¬eÄ=4Ì-†µ­‡PLÅj^¹ž«', '1zzz333888lll111ttiiiimmmnnnfffp'),
('ðœ¨ÇªZ³VB!üÆ8þ®¨', 'ß!hÑŽÊ³AÓiÛKtÇ©i§E\0~¬© \\ñžBr=ûÛ>Ám¥\Z¤½:S\"5GZg¡cÏ\rÇçEqŠ{ãþ‘€d!@(q­Ë[=1Ä¥;¬', '0k33888pppuuugg000xxxhhhrroooaaa'),
('…÷&þ¡½Ú™a w‡', '·-dÔ±Lÿ-Ïvm§qjÀž', '0k33888pppuuugg000xxxhhhrroooaaa'),
('[ä ó¾éF¼¥[ò“M	-Œ', 'y\"Ãºlƒ,„ù®w5\r+d', '0k33888pppuuugg000xxxhhhrroooaaa'),
('äúfæ\Z\0G´üùUB', '3N¿>S–Ï›¬Ö\Zù!šrê:-\'}[ûàïûÉõzw*', '0k33888pppuuugg000xxxhhhrroooaaa'),
('^Á3\0è×fø\0[.UN', '19Ö¬àø>%„%ÃzÃåüÎ', '0k33888pppuuugg000xxxhhhrroooaaa'),
('ˆe`òuéUfõêcrOú', '‹ ›.¦v§Q\n:Ø	Ê', '0k33888pppuuugg000xxxhhhrroooaaa'),
('ïíÓê1%ådþ½ù;¦', 'Q~‹((‘w’úyeÁ‘i»', '0k33888pppuuugg000xxxhhhrroooaaa'),
('#]N„S‰Ó\\	Ð» ×', '\rîM÷„ÅÒØpáªKÀåâÀÁíNb,0`R¶`òiÙ&4JC&Õ«Áxº,S5Ë›uˆi\n„HšNµˆMÍÙK‡*¶ŒFúïîâÿÍ’', '0k33888pppuuugg000xxxhhhrroooaaa'),
('ó}ß}ª“åHngåål', '¯\rÖþŽf¹¸¼4Ò¡Îs\"ÏwÚeµiÖ@0ú<õ°&<´zWì\'¤‚©\rxgÜ?MÓû…êœD„o3aÖÓð§–4.×NbSpÊû‰HfLËï}çLH?FAñ‹ëÞÇêïÅÚ@b\nëO–ˆzXN•—>nÀ\Zôòö„€TÌˆ)Ö¾¨áMlÖ;2mÍŠåóá60&»ï\'uŒò/Ìî¥]jRÓ„fñŠ|-·É0gòÔR¿Ô4ÀÅPçØh\rÁ‹¥‡³öL¿ ÕY–¡1N˜v­oŠóPÒ³8R_Œõ&3—qEÌË-¹5¸|˜-÷‰Ü?Iî °øXô™œ]kf ”nç›lp=UcºA,þÂ,ñíô‡©â^ie·²cõ2¼eú²ë!{è½ŒR”ô&úð-Å<¾ iŽö#d#âp¿„û(­J›­0úíªm-Lnðÿù^sŽrí¸ª\0Cë¤;s£öb]å¨ðÿ=/Ýuvƒ5¡µ¥){ç›d¼Q}OaÜAA³ï;úË-rÃ‘»aÎ_`Öù‹(ìMß<üU3«3yL~½¡ù&iûÐ…»RŠ™Üé-“ã;…úÿ}Ap…7Æ†·ÒIcwLç€47ÖS-¾€Š\Zº¨]Ó@\naù±éOlbzgÛæj³9KŸæ¦d¸6Là:ÛfgÁÉ1ë	å(×#+ÞúÁ11ö„€-\'ç˜÷.LãE\Z+	‹B„w!Ð0S9«*Îa™&ûC­x\"ªØ©P39=ŸaPæäŒæ~aª*Ÿa6—b½×k†;Ùi+ö\"0sõh?ÑáõÚJèˆ)óÓZ€ì[Øl¢ü–ÖtÅÆB™¯âŠmër¾°.‹:‰Hœêwü±øL«.(¾‘L”é\r ¥aà½¦Å´Ï+’-S’^Uot&.ä`p_+ÕVü©‰=7ÒË¬óH\rp1:„ þÌ<Úøc¶ªáðõSµ\ZÂÌÕ;FfÛ ­’4{?ÝÕÐÒ§weVò{4u¾Û£¼øz3ÃÞ„Žæƒ\\çdÛE^+ï¸{ýOí±<vmöÄÃ)’¢ó|pý´û…2gSV‹´5~%æM— šÃoò´éãeTr€Ï£ôÜ¯ª´†</Ð!\rãÊ€~‡õì”µÍ†’IdæT‹Š«ãUW	}_³9ðOèYªÜhÎŽ\nãi7ù<y\nùŽ\nØ±êùŽ¶³†úÍ`gi¶ƒû‹u‹[‘©º‹2X³<›¨¢Îa³OÞäOPÏSÐžû%™þfh…Œ	T0ÍÎêh«È—Ò˜ÌªpÄ£&9Rè\'g96; Œ{„‰/\0ÀHàáóÙ·ÄòTq\Z9Ñú7¡•—ùš5ìL{BÛ­6e:i¬ÿ5Œ/H3-]s¥vì/’\"êùˆå“cÁ7ô~Š¯Q$ËFÓpcköœ @F•à:LŒÎi0¦/‚0ä´ûz´}Û­ë5Å«3fÏ·«ù¬2: 4¦³õ³Kñ§­Ç¿Ò¾Ù‚Æ\\ÞÆ¾ò»:‹?X°¯ÈÒælÇl\Zîçþ\rl>#ûfŒ©Ï)Ÿ$pY:ó˜kLgQø1’ùÏ&ø\Z+Id‹[OE¢;}\'³¹Òì÷u\\¼ñÉhÇË4rý´3\0’„&“u©rhEÉœß#éE©zªFÂ6ÑŸŠ.b>@Ã¦åò`úå¦¼™\0£Õ½\Zw \\ª¸ÈQ{oCÉndvV¹¤ùhûu*Pâ¥uoWNè¦J‘àŠÌ\\™IL¼ÒM.A¤Xí¼ò…+zõ‰ŸÒp/}G2;¾\Z§^j/fEá?`	ùo:MÃ	›‹§tQ\'&K>#Ô”zPÎ˜½Ð\0\"ú\\u·þwÑæ..Œ]u u††©j=-tÖ\"u‚y\Z/ƒ‘—Pc‡»ÜÍ(‡eÓµ¾+xüañÐ|eLMw\'©µËçÏÉŸ4yV±/WïÙ-¡è\ZE“b#t½uUtóed]z|œXmwrŸª…ÁÉ|¡qòYÈZñ¯¾òE¦GäÁã„GÚyNŽb¹Íš&oÒ¯³±ÊÚmÈ UH|4ðŽ¢ôšlIQV-<B7À¸B\rÇÍÿPVE\'ÁsCæZØŽ\"ƒ’\rêØ^”i\rˆü¸±¤ÍöpÊX†fºÑ$Z2|•	‰~]Yûí,ó+äpúIà31´øŒ2Õmxq5“7²Gú­e`ÀxÛßÂå/Íä-O,êÐ\r>DÁ–Éùš4T´)_`÷ÿ½?62C«†{£°‰€œ6ªnðgÈ¥=µÖ:$×Ô/ó¯§»šj0arøÂõM\"|cÒVlBºœ\\Ì\0RÈé«ErF\rrÀ³üz”ýÄÙBQŸªC3…I’8è¾üÀÜŠŠÍÔÐêì×Ç5fÜª\rf¼P…»mC%eUÐŸŒÍø:…Ž/$ŒntÉt!gu¾257éÂ}@÷Ãƒ€\ZŠ\nþ31‚ò\n$e…†UmrÛ°þ>®ÓµIï_ q€Ë¿¸’¦Wwîü¶Ü«Óð ƒA‚@ Ú%V²æW­:Ø\'i‹øËù¸ÔmÖ!«£UÑi”Lø†6G\Z,èlƒÁø#µž×¦Ã–ï·\'Ð7cýà@÷’ü‡®{ÄÕù²Íuëo*óQÖÐ$ôÕ•àÌŽ¬`,”rp)G]EÈPýþu`ÇøCC5á©ý6•\nc•ÏUñ\"Žh7¨Qž°×Ä²ÖI›HªRWeÇ[ÿä/µè5‚ ôòÜ¸$Sôgi¯éLìÂ2Ty¸WAXî¹u\rö0´ï‘”áÛÈ\0p°=Mˆä\"-’ù¿¨\Z— ÏFÞ’\rÌ1krõŸ•ÿ|Ø,e­\\X­¡fèñð‡‡G÷ ã’ÃßÔ,!úL»q‰ªÖ“ibÂZëŸhÜÙæ×Tx-¤{GVtÈ~°få°À=O²ÿFM;GRì®6ž+Â™1wmTÍ¶±3«=R¿IaŽ>ÿFÚ–ª5rß³¨è•%o }â]ÌYÆSWìx*KW ¦ìßLa¶ z¶å!ºŠ2D²±¡…Y†p¶·U‡•;²¯#ã”Óx\rýÐZ)÷ñ^§ITO¼_A/Ý\ZýØœU­l#ÔßòËœ`x°ü6?odp\\dõJÓI´Ò— IôŠ¶4J;Íz||›$oº:pVi³\"é{¾q‰áÚq–ËMoÆ“¡ê¨tÊãrÈbd®ð?Óp\r<#|sVß0í4%úf·ëXšçßAŒTññÚŸì)þeô\"„Ù\ZÁ|ª‡]rd<hŸNÄD5çZ²ÑÃa%a²ÙlþÊ—[pÓ\'I‹B¶X‰„ûf¬~l6Óí‚G ðèV™-#ÜA,–×l±’¾Ðô¸\\â0»Uõ1%ólÆû·x\Z®èÎŒZÕ¥fÓ§Äim«Ð$Rùï¼`e[ë—fMímU‘#GýöEªw”K\"\nöŸÕ$2ìÊåÉýë—’¿oñ‘Qi‘¾+ÀÙç—sZ#£¶Œà7 s‰ðèë‰óy_7™ÐDÈÁ|\\÷â³»$êê@ÜÐw˜•OÆ¶Å›æh¥j»â²4¸³À;Õò;9$\r#œxÄ˜Aò•úªkâ2o~½Ôšî¤Ðµ+îá•–nz‡/þÝ³]\rïKŠÝ/\r•t!füuÒÔÿí¦èÓUD>¹9’,k}-ã>Ž0š¿Hgçˆ™nÉòÿòë1.ÕtðXggÁ‹ >7¿Ö/mF›B£¹³ywúÑŒa°‰ØI€‰OAöÚ~~¦éH7¾’¹Yã|•ëËwßC‡P~™&þbü\Z\\ ÃˆPÌaQÆINÅÎ-*b-ß=ß¢FÐ°;!ÑÌäŸÈ…€6‰\ZÖ‰?«ëcG“ÿÞ~t˜l;Ëg%¯H7ˆî”ÿu–Ã^ñ–´B\r5PsKŸS¡\nÍ’VWpŸðoIbjèòå1­ëö‹Æí?VoI\0E‘]—[vÃu°ó¯u¾õmÎfå!|¤žä\\lRÏGO‚K1½¦)~ýÌ\rK$•ÜòÄÅÕÏ–¦7oÒ	A°DZ	2KÌSx\"¬¬kto2{„3üÌ.‰|/úðæÅf9‚ç±þ‹¨†þ­§éºÜn9ò‹¬eÄ=4Ì-†µ­‡PLÅj^¹ž«', '0k33888pppuuugg000xxxhhhrroooaaa'),
('ðœ¨ÇªZ³VB!üÆ8þ®¨', 'öâeíÊtÕÚjxú_ì3ã%«·¦4](pï2“ªÊß}úi§E\0~¬© \\ñžBrã¤ÒÚ?DfÎƒ\Z¦j½°FDx¿~gˆãèÉDeN', 'qccxxxnnfffwwwqqggg000kkhhh77ooo'),
('…÷&þ¡½Ú™a w‡', '·-dÔ±Lÿ-Ïvm§qjÀž', 'qccxxxnnfffwwwqqggg000kkhhh77ooo'),
('[ä ó¾éF¼¥[ò“M	-Œ', 'y\"Ãºlƒ,„ù®w5\r+d', 'qccxxxnnfffwwwqqggg000kkhhh77ooo'),
('äúfæ\Z\0G´üùUB', '3N¿>S–Ï›¬Ö\Zù!šrê:-\'}[ûàïûÉõzw*', 'qccxxxnnfffwwwqqggg000kkhhh77ooo'),
('^Á3\0è×fø\0[.UN', '19Ö¬àø>%„%ÃzÃåüÎ', 'qccxxxnnfffwwwqqggg000kkhhh77ooo'),
('ˆe`òuéUfõêcrOú', '‹ ›.¦v§Q\n:Ø	Ê', 'qccxxxnnfffwwwqqggg000kkhhh77ooo'),
('ïíÓê1%ådþ½ù;¦', 'Q~‹((‘w’úyeÁ‘i»', 'qccxxxnnfffwwwqqggg000kkhhh77ooo'),
('#]N„S‰Ó\\	Ð» ×', '\rîM÷„ÅÒØpáªKÀåâÀÁíNb,0`R¶`òiÙ&4JC&Õ«Áxº,S5Ë›uˆi\n„HšNµˆMÍÙK‡*¶ŒFúïîâÿÍ’', 'qccxxxnnfffwwwqqggg000kkhhh77ooo'),
('ó}ß}ª“åHngåål', '¯\rÖþŽf¹¸¼4Ò¡Îs\"ÏwÚeµiÖ@0ú<õ°&<´zWì\'¤‚©\rxgÜ?MÓû…êœD„o3aÖÓð§–4.×NbSpÊû‰HfLËï}çLH?FAñ‹ëÞÇêïÅÚ@b\nëO–ˆzXN•—>nÀ\Zôòö„€TÌˆ)Ö¾¨áMlÖ;2mÍŠåóá60&»ï\'uŒò/Ìî¥]jRÓ„fñŠ|-·É0gòÔR¿Ô4ÀÅPçØh\rÁ‹¥‡³öL¿ ÕY–¡1N˜v­oŠóPÒ³8R_Œõ&3—qEÌË-¹5¸|˜-÷‰Ü?Iî °øXô™œ]kf ”nç›lp=UcºA,þÂ,ñíô‡©â^ie·²cõ2¼eú²ë!{è½ŒR”ô&úð-Å<¾ iŽö#d#âp¿„û(­J›­0úíªm-Lnðÿù^sŽrí¸ª\0Cë¤;s£öb]å¨ðÿ=/Ýuvƒ5¡µ¥){ç›d¼Q}OaÜAA³ï;úË-rÃ‘»aÎ_`Öù‹(ìMß<üU3«3yL~½¡ù&iûÐ…»RŠ™Üé-“ã;…úÿ}Ap…7Æ†·ÒIcwLç€47ÖS-¾€Š\Zº¨]Ó@\naù±éOlbzgÛæj³9KŸæ¦d¸6Là:ÛfgÁÉ1ë	å(×#+ÞúÁ11ö„€-\'ç˜÷.LãE\Z+	‹B„w!Ð0S9«*Îa™&ûC­x\"ªØ©P39=ŸaPæäŒæ~aª*Ÿa6—b½×k†;Ùi+ö\"0sõh?ÑáõÚJèˆ)óÓZ€ì[Øl¢ü–ÖtÅÆB™¯âŠmër¾°.‹:‰Hœêwü±øL«.(¾‘L”é\r ¥aà½¦Å´Ï+’-S’^Uot&.ä`p_+ÕVü©‰=7ÒË¬óH\rp1:„ þÌ<Úøc¶ªáðõSµ\ZÂÌÕ;FfÛ ­’4{?ÝÕÐÒ§weVò{4u¾Û£¼øz3ÃÞ„Žæƒ\\çdÛE^+ï¸{ýOí±<vmöÄÃ)’¢ó|pý´û…2gSV‹´5~%æM— šÃoò´éãeTr€Ï£ôÜ¯ª´†</Ð!\rãÊ€~‡õì”µÍ†’IdæT‹Š«ãUW	}_³9ðOèYªÜhÎŽ\nãi7ù<y\nùŽ\nØ±êùŽ¶³†úÍ`gi¶ƒû‹u‹[‘©º‹2X³<›¨¢Îa³OÞäOPÏSÐžû%™þfh…Œ	T0ÍÎêh«È—Ò˜ÌªpÄ£&9Rè\'g96; Œ{„‰/\0ÀHàáóÙ·ÄòTq\Z9Ñú7¡•—ùš5ìL{BÛ­6e:i¬ÿ5Œ/H3-]s¥vì/’\"êùˆå“cÁ7ô~Š¯Q$ËFÓpcköœ @F•à:LŒÎi0¦/‚0ä´ûz´}Û­ë5Å«3fÏ·«ù¬2: 4¦³õ³Kñ§­Ç¿Ò¾Ù‚Æ\\ÞÆ¾ò»:‹?X°¯ÈÒælÇl\Zîçþ\rl>#ûfŒ©Ï)Ÿ$pY:ó˜kLgQø1’ùÏ&ø\Z+Id‹[OE¢;}\'³¹Òì÷u\\¼ñÉhÇË4rý´3\0’„&“u©rhEÉœß#éE©zªFÂ6ÑŸŠ.b>@Ã¦åò`úå¦¼™\0£Õ½\Zw \\ª¸ÈQ{oCÉndvV¹¤ùhûu*Pâ¥uoWNè¦J‘àŠÌ\\™IL¼ÒM.A¤Xí¼ò…+zõ‰ŸÒp/}G2;¾\Z§^j/fEá?`	ùo:MÃ	›‹§tQ\'&K>#Ô”zPÎ˜½Ð\0\"ú\\u·þwÑæ..Œ]u u††©j=-tÖ\"u‚y\Z/ƒ‘—Pc‡»ÜÍ(‡eÓµ¾+xüañÐ|eLMw\'©µËçÏÉŸ4yV±/WïÙ-¡è\ZE“b#t½uUtóed]z|œXmwrŸª…ÁÉ|¡qòYÈZñ¯¾òE¦GäÁã„GÚyNŽb¹Íš&oÒ¯³±ÊÚmÈ UH|4ðŽ¢ôšlIQV-<B7À¸B\rÇÍÿPVE\'ÁsCæZØŽ\"ƒ’\rêØ^”i\rˆü¸±¤ÍöpÊX†fºÑ$Z2|•	‰~]Yûí,ó+äpúIà31´øŒ2Õmxq5“7²Gú­e`ÀxÛßÂå/Íä-O,êÐ\r>DÁ–Éùš4T´)_`÷ÿ½?62C«†{£°‰€œ6ªnðgÈ¥=µÖ:$×Ô/ó¯§»šj0arøÂõM\"|cÒVlBºœ\\Ì\0RÈé«ErF\rrÀ³üz”ýÄÙBQŸªC3…I’8è¾üÀÜŠŠÍÔÐêì×Ç5fÜª\rf¼P…»mC%eUÐŸŒÍø:…Ž/$ŒntÉt!gu¾257éÂ}@÷Ãƒ€\ZŠ\nþ31‚ò\n$e…†UmrÛ°þ>®ÓµIï_ q€Ë¿¸’¦Wwîü¶Ü«Óð ƒA‚@ Ú%V²æW­:Ø\'i‹øËù¸ÔmÖ!«£UÑi”Lø†6G\Z,èlƒÁø#µž×¦Ã–ï·\'Ð7cýà@÷’ü‡®{ÄÕù²Íuëo*óQÖÐ$ôÕ•àÌŽ¬`,”rp)G]EÈPýþu`ÇøCC5á©ý6•\nc•ÏUñ\"Žh7¨Qž°×Ä²ÖI›HªRWeÇ[ÿä/µè5‚ ôòÜ¸$Sôgi¯éLìÂ2Ty¸WAXî¹u\rö0´ï‘”áÛÈ\0p°=Mˆä\"-’ù¿¨\Z— ÏFÞ’\rÌ1krõŸ•ÿ|Ø,e­\\X­¡fèñð‡‡G÷ ã’ÃßÔ,!úL»q‰ªÖ“ibÂZëŸhÜÙæ×Tx-¤{GVtÈ~°få°À=O²ÿFM;GRì®6ž+Â™1wmTÍ¶±3«=R¿IaŽ>ÿFÚ–ª5rß³¨è•%o }â]ÌYÆSWìx*KW ¦ìßLa¶ z¶å!ºŠ2D²±¡…Y†p¶·U‡•;²¯#ã”Óx\rýÐZ)÷ñ^§ITO¼_A/Ý\ZýØœU­l#ÔßòËœ`x°ü6?odp\\dõJÓI´Ò— IôŠ¶4J;Íz||›$oº:pVi³\"é{¾q‰áÚq–ËMoÆ“¡ê¨tÊãrÈbd®ð?Óp\r<#|sVß0í4%úf·ëXšçßAŒTññÚŸì)þeô\"„Ù\ZÁ|ª‡]rd<hŸNÄD5çZ²ÑÃa%a²ÙlþÊ—[pÓ\'I‹B¶X‰„ûf¬~l6Óí‚G ðèV™-#ÜA,–×l±’¾Ðô¸\\â0»Uõ1%ólÆû·x\Z®èÎŒZÕ¥fÓ§Äim«Ð$Rùï¼`e[ë—fMímU‘#GýöEªw”K\"\nöŸÕ$2ìÊåÉýë—’¿oñ‘Qi‘¾+ÀÙç—sZ#£¶Œà7 s‰ðèë‰óy_7™ÐDÈÁ|\\÷â³»$êê@ÜÐw˜•OÆ¶Å›æh¥j»â²4¸³À;Õò;9$\r#œxÄ˜Aò•úªkâ2o~½Ôšî¤Ðµ+îá•–nz‡/þÝ³]\rïKŠÝ/\r•t!füuÒÔÿí¦èÓUD>¹9’,k}-ã>Ž0š¿Hgçˆ™nÉòÿòë1.ÕtðXggÁ‹ >7¿Ö/mF›B£¹³ywúÑŒa°‰ØI€‰OAöÚ~~¦éH7¾’¹Yã|•ëËwßC‡P~™&þbü\Z\\ ÃˆPÌaQÆINÅÎ-*b-ß=ß¢FÐ°;!ÑÌäŸÈ…€6‰\ZÖ‰?«ëcG“ÿÞ~t˜l;Ëg%¯H7ˆî”ÿu–Ã^ñ–´B\r5PsKŸS¡\nÍ’VWpŸðoIbjèòå1­ëö‹Æí?VoI\0E‘]—[vÃu°ó¯u¾õmÎfå!|¤žä\\lRÏGO‚K1½¦)~ýÌ\rK$•ÜòÄÅÕÏ–¦7oÒ	A°DZ	2KÌSx\"¬¬kto2{„3üÌ.‰|/úðæÅf9‚ç±þ‹¨†þ­§éºÜn9ò‹¬eÄ=4Ì-†µ­‡PLÅj^¹ž«', 'qccxxxnnfffwwwqqggg000kkhhh77ooo'),
('ðœ¨ÇªZ³VB!üÆ8þ®¨', 'ûocÂ,çÃâ™Y§ÏóÔ½i§E\0~¬© \\ñžBr=ûÛ>Ám¥\Z¤½:S\"5GZjTÙdVï \r‰.jsg8œ¯‹>Ë³)×žU²', '2eeeaabbbvvvll111777oooaaabbvvvs'),
('…÷&þ¡½Ú™a w‡', '·-dÔ±Lÿ-Ïvm§qjÀž', '2eeeaabbbvvvll111777oooaaabbvvvs'),
('[ä ó¾éF¼¥[ò“M	-Œ', 'y\"Ãºlƒ,„ù®w5\r+d', '2eeeaabbbvvvll111777oooaaabbvvvs'),
('äúfæ\Z\0G´üùUB', '3N¿>S–Ï›¬Ö\Zù!šrê:-\'}[ûàïûÉõzw*', '2eeeaabbbvvvll111777oooaaabbvvvs'),
('^Á3\0è×fø\0[.UN', '19Ö¬àø>%„%ÃzÃåüÎ', '2eeeaabbbvvvll111777oooaaabbvvvs'),
('ˆe`òuéUfõêcrOú', '‹ ›.¦v§Q\n:Ø	Ê', '2eeeaabbbvvvll111777oooaaabbvvvs'),
('ïíÓê1%ådþ½ù;¦', 'Q~‹((‘w’úyeÁ‘i»', '2eeeaabbbvvvll111777oooaaabbvvvs'),
('#]N„S‰Ó\\	Ð» ×', '\rîM÷„ÅÒØpáªKÀåâÀÁíNb,0`R¶`òiÙ&4JC&Õ«Áxº,S5Ë›uˆi\n„HšNµˆMÍÙK‡*¶ŒFúïîâÿÍ’', '2eeeaabbbvvvll111777oooaaabbvvvs'),
('ó}ß}ª“åHngåål', '¯\rÖþŽf¹¸¼4Ò¡Îs\"ÏwÚeµiÖ@0ú<õ°&<´zWì\'¤‚©\rxgÜ?MÓû…êœD„o3aÖÓð§–4.×NbSpÊû‰HfLËï}çLH?FAñ‹ëÞÇêïÅÚ@b\nëO–ˆzXN•—>nÀ\Zôòö„€TÌˆ)Ö¾¨áMlÖ;2mÍŠåóá60&»ï\'uŒò/Ìî¥]jRÓ„fñŠ|-·É0gòÔR¿Ô4ÀÅPçØh\rÁ‹¥‡³öL¿ ÕY–¡1N˜v­oŠóPÒ³8R_Œõ&3—qEÌË-¹5¸|˜-÷‰Ü?Iî °øXô™œ]kf ”nç›lp=UcºA,þÂ,ñíô‡©â^ie·²cõ2¼eú²ë!{è½ŒR”ô&úð-Å<¾ iŽö#d#âp¿„û(­J›­0úíªm-Lnðÿù^sŽrí¸ª\0Cë¤;s£öb]å¨ðÿ=/Ýuvƒ5¡µ¥){ç›d¼Q}OaÜAA³ï;úË-rÃ‘»aÎ_`Öù‹(ìMß<üU3«3yL~½¡ù&iûÐ…»RŠ™Üé-“ã;…úÿ}Ap…7Æ†·ÒIcwLç€47ÖS-¾€Š\Zº¨]Ó@\naù±éOlbzgÛæj³9KŸæ¦d¸6Là:ÛfgÁÉ1ë	å(×#+ÞúÁ11ö„€-\'ç˜÷.LãE\Z+	‹B„w!Ð0S9«*Îa™&ûC­x\"ªØ©P39=ŸaPæäŒæ~aª*Ÿa6—b½×k†;Ùi+ö\"0sõh?ÑáõÚJèˆ)óÓZ€ì[Øl¢ü–ÖtÅÆB™¯âŠmër¾°.‹:‰Hœêwü±øL«.(¾‘L”é\r ¥aà½¦Å´Ï+’-S’^Uot&.ä`p_+ÕVü©‰=7ÒË¬óH\rp1:„ þÌ<Úøc¶ªáðõSµ\ZÂÌÕ;FfÛ ­’4{?ÝÕÐÒ§weVò{4u¾Û£¼øz3ÃÞ„Žæƒ\\çdÛE^+ï¸{ýOí±<vmöÄÃ)’¢ó|pý´û…2gSV‹´5~%æM— šÃoò´éãeTr€Ï£ôÜ¯ª´†</Ð!\rãÊ€~‡õì”µÍ†’IdæT‹Š«ãUW	}_³9ðOèYªÜhÎŽ\nãi7ù<y\nùŽ\nØ±êùŽ¶³†úÍ`gi¶ƒû‹u‹[‘©º‹2X³<›¨¢Îa³OÞäOPÏSÐžû%™þfh…Œ	T0ÍÎêh«È—Ò˜ÌªpÄ£&9Rè\'g96; Œ{„‰/\0ÀHàáóÙ·ÄòTq\Z9Ñú7¡•—ùš5ìL{BÛ­6e:i¬ÿ5Œ/H3-]s¥vì/’\"êùˆå“cÁ7ô~Š¯Q$ËFÓpcköœ @F•à:LŒÎi0¦/‚0ä´ûz´}Û­ë5Å«3fÏ·«ù¬2: 4¦³õ³Kñ§­Ç¿Ò¾Ù‚Æ\\ÞÆ¾ò»:‹?X°¯ÈÒælÇl\Zîçþ\rl>#ûfŒ©Ï)Ÿ$pY:ó˜kLgQø1’ùÏ&ø\Z+Id‹[OE¢;}\'³¹Òì÷u\\¼ñÉhÇË4rý´3\0’„&“u©rhEÉœß#éE©zªFÂ6ÑŸŠ.b>@Ã¦åò`úå¦¼™\0£Õ½\Zw \\ª¸ÈQ{oCÉndvV¹¤ùhûu*Pâ¥uoWNè¦J‘àŠÌ\\™IL¼ÒM.A¤Xí¼ò…+zõ‰ŸÒp/}G2;¾\Z§^j/fEá?`	ùo:MÃ	›‹§tQ\'&K>#Ô”zPÎ˜½Ð\0\"ú\\u·þwÑæ..Œ]u u††©j=-tÖ\"u‚y\Z/ƒ‘—Pc‡»ÜÍ(‡eÓµ¾+xüañÐ|eLMw\'©µËçÏÉŸ4yV±/WïÙ-¡è\ZE“b#t½uUtóed]z|œXmwrŸª…ÁÉ|¡qòYÈZñ¯¾òE¦GäÁã„GÚyNŽb¹Íš&oÒ¯³±ÊÚmÈ UH|4ðŽ¢ôšlIQV-<B7À¸B\rÇÍÿPVE\'ÁsCæZØŽ\"ƒ’\rêØ^”i\rˆü¸±¤ÍöpÊX†fºÑ$Z2|•	‰~]Yûí,ó+äpúIà31´øŒ2Õmxq5“7²Gú­e`ÀxÛßÂå/Íä-O,êÐ\r>DÁ–Éùš4T´)_`÷ÿ½?62C«†{£°‰€œ6ªnðgÈ¥=µÖ:$×Ô/ó¯§»šj0arøÂõM\"|cÒVlBºœ\\Ì\0RÈé«ErF\rrÀ³üz”ýÄÙBQŸªC3…I’8è¾üÀÜŠŠÍÔÐêì×Ç5fÜª\rf¼P…»mC%eUÐŸŒÍø:…Ž/$ŒntÉt!gu¾257éÂ}@÷Ãƒ€\ZŠ\nþ31‚ò\n$e…†UmrÛ°þ>®ÓµIï_ q€Ë¿¸’¦Wwîü¶Ü«Óð ƒA‚@ Ú%V²æW­:Ø\'i‹øËù¸ÔmÖ!«£UÑi”Lø†6G\Z,èlƒÁø#µž×¦Ã–ï·\'Ð7cýà@÷’ü‡®{ÄÕù²Íuëo*óQÖÐ$ôÕ•àÌŽ¬`,”rp)G]EÈPýþu`ÇøCC5á©ý6•\nc•ÏUñ\"Žh7¨Qž°×Ä²ÖI›HªRWeÇ[ÿä/µè5‚ ôòÜ¸$Sôgi¯éLìÂ2Ty¸WAXî¹u\rö0´ï‘”áÛÈ\0p°=Mˆä\"-’ù¿¨\Z— ÏFÞ’\rÌ1krõŸ•ÿ|Ø,e­\\X­¡fèñð‡‡G÷ ã’ÃßÔ,!úL»q‰ªÖ“ibÂZëŸhÜÙæ×Tx-¤{GVtÈ~°få°À=O²ÿFM;GRì®6ž+Â™1wmTÍ¶±3«=R¿IaŽ>ÿFÚ–ª5rß³¨è•%o }â]ÌYÆSWìx*KW ¦ìßLa¶ z¶å!ºŠ2D²±¡…Y†p¶·U‡•;²¯#ã”Óx\rýÐZ)÷ñ^§ITO¼_A/Ý\ZýØœU­l#ÔßòËœ`x°ü6?odp\\dõJÓI´Ò— IôŠ¶4J;Íz||›$oº:pVi³\"é{¾q‰áÚq–ËMoÆ“¡ê¨tÊãrÈbd®ð?Óp\r<#|sVß0í4%úf·ëXšçßAŒTññÚŸì)þeô\"„Ù\ZÁ|ª‡]rd<hŸNÄD5çZ²ÑÃa%a²ÙlþÊ—[pÓ\'I‹B¶X‰„ûf¬~l6Óí‚G ðèV™-#ÜA,–×l±’¾Ðô¸\\â0»Uõ1%ólÆû·x\Z®èÎŒZÕ¥fÓ§Äim«Ð$Rùï¼`e[ë—fMímU‘#GýöEªw”K\"\nöŸÕ$2ìÊåÉýë—’¿oñ‘Qi‘¾+ÀÙç—sZ#£¶Œà7 s‰ðèë‰óy_7™ÐDÈÁ|\\÷â³»$êê@ÜÐw˜•OÆ¶Å›æh¥j»â²4¸³À;Õò;9$\r#œxÄ˜Aò•úªkâ2o~½Ôšî¤Ðµ+îá•–nz‡/þÝ³]\rïKŠÝ/\r•t!füuÒÔÿí¦èÓUD>¹9’,k}-ã>Ž0š¿Hgçˆ™nÉòÿòë1.ÕtðXggÁ‹ >7¿Ö/mF›B£¹³ywúÑŒa°‰ØI€‰OAöÚ~~¦éH7¾’¹Yã|•ëËwßC‡P~™&þbü\Z\\ ÃˆPÌaQÆINÅÎ-*b-ß=ß¢FÐ°;!ÑÌäŸÈ…€6‰\ZÖ‰?«ëcG“ÿÞ~t˜l;Ëg%¯H7ˆî”ÿu–Ã^ñ–´B\r5PsKŸS¡\nÍ’VWpŸðoIbjèòå1­ëö‹Æí?VoI\0E‘]—[vÃu°ó¯u¾õmÎfå!|¤žä\\lRÏGO‚K1½¦)~ýÌ\rK$•ÜòÄÅÕÏ–¦7oÒ	A°DZ	2KÌSx\"¬¬kto2{„3üÌ.‰|/úðæÅf9‚ç±þ‹¨†þ­§éºÜn9ò‹¬eÄ=4Ì-†µ­‡PLÅj^¹ž«', '2eeeaabbbvvvll111777oooaaabbvvvs'),
('ðœ¨ÇªZ³VB!üÆ8þ®¨', '…‘Ü›¤WÞ…Ì²“Ô0PÒ«·¦4](pï2“ªÊß}úi§E\0~¬© \\ñžBrw9F·6?WÒ€c2Ê$PÆ.j­ýK {”w>', 'db999ll111666ddmmm333ffwwwyyyttt'),
('…÷&þ¡½Ú™a w‡', '·-dÔ±Lÿ-Ïvm§qjÀž', 'db999ll111666ddmmm333ffwwwyyyttt'),
('[ä ó¾éF¼¥[ò“M	-Œ', 'y\"Ãºlƒ,„ù®w5\r+d', 'db999ll111666ddmmm333ffwwwyyyttt'),
('äúfæ\Z\0G´üùUB', '3N¿>S–Ï›¬Ö\Zù!šrê:-\'}[ûàïûÉõzw*', 'db999ll111666ddmmm333ffwwwyyyttt'),
('^Á3\0è×fø\0[.UN', '19Ö¬àø>%„%ÃzÃåüÎ', 'db999ll111666ddmmm333ffwwwyyyttt'),
('ˆe`òuéUfõêcrOú', '‹ ›.¦v§Q\n:Ø	Ê', 'db999ll111666ddmmm333ffwwwyyyttt'),
('ïíÓê1%ådþ½ù;¦', 'Q~‹((‘w’úyeÁ‘i»', 'db999ll111666ddmmm333ffwwwyyyttt'),
('#]N„S‰Ó\\	Ð» ×', '\rîM÷„ÅÒØpáªKÀåâÀÁíNb,0`R¶`òiÙ&4JC&Õ«Áxº,S5Ë›uˆi\n„HšNµˆMÍÙK‡*¶ŒFúïîâÿÍ’', 'db999ll111666ddmmm333ffwwwyyyttt'),
('ó}ß}ª“åHngåål', '¯\rÖþŽf¹¸¼4Ò¡Îs\"ÏwÚeµiÖ@0ú<õ°&<´zWì\'¤‚©\rxgÜ?MÓû…êœD„o3aÖÓð§–4.×NbSpÊû‰HfLËï}çLH?FAñ‹ëÞÇêïÅÚ@b\nëO–ˆzXN•—>nÀ\Zôòö„€TÌˆ)Ö¾¨áMlÖ;2mÍŠåóá60&»ï\'uŒò/Ìî¥]jRÓ„fñŠ|-·É0gòÔR¿Ô4ÀÅPçØh\rÁ‹¥‡³öL¿ ÕY–¡1N˜v­oŠóPÒ³8R_Œõ&3—qEÌË-¹5¸|˜-÷‰Ü?Iî °øXô™œ]kf ”nç›lp=UcºA,þÂ,ñíô‡©â^ie·²cõ2¼eú²ë!{è½ŒR”ô&úð-Å<¾ iŽö#d#âp¿„û(­J›­0úíªm-Lnðÿù^sŽrí¸ª\0Cë¤;s£öb]å¨ðÿ=/Ýuvƒ5¡µ¥){ç›d¼Q}OaÜAA³ï;úË-rÃ‘»aÎ_`Öù‹(ìMß<üU3«3yL~½¡ù&iûÐ…»RŠ™Üé-“ã;…úÿ}Ap…7Æ†·ÒIcwLç€47ÖS-¾€Š\Zº¨]Ó@\naù±éOlbzgÛæj³9KŸæ¦d¸6Là:ÛfgÁÉ1ë	å(×#+ÞúÁ11ö„€-\'ç˜÷.LãE\Z+	‹B„w!Ð0S9«*Îa™&ûC­x\"ªØ©P39=ŸaPæäŒæ~aª*Ÿa6—b½×k†;Ùi+ö\"0sõh?ÑáõÚJèˆ)óÓZ€ì[Øl¢ü–ÖtÅÆB™¯âŠmër¾°.‹:‰Hœêwü±øL«.(¾‘L”é\r ¥aà½¦Å´Ï+’-S’^Uot&.ä`p_+ÕVü©‰=7ÒË¬óH\rp1:„ þÌ<Úøc¶ªáðõSµ\ZÂÌÕ;FfÛ ­’4{?ÝÕÐÒ§weVò{4u¾Û£¼øz3ÃÞ„Žæƒ\\çdÛE^+ï¸{ýOí±<vmöÄÃ)’¢ó|pý´û…2gSV‹´5~%æM— šÃoò´éãeTr€Ï£ôÜ¯ª´†</Ð!\rãÊ€~‡õì”µÍ†’IdæT‹Š«ãUW	}_³9ðOèYªÜhÎŽ\nãi7ù<y\nùŽ\nØ±êùŽ¶³†úÍ`gi¶ƒû‹u‹[‘©º‹2X³<›¨¢Îa³OÞäOPÏSÐžû%™þfh…Œ	T0ÍÎêh«È—Ò˜ÌªpÄ£&9Rè\'g96; Œ{„‰/\0ÀHàáóÙ·ÄòTq\Z9Ñú7¡•—ùš5ìL{BÛ­6e:i¬ÿ5Œ/H3-]s¥vì/’\"êùˆå“cÁ7ô~Š¯Q$ËFÓpcköœ @F•à:LŒÎi0¦/‚0ä´ûz´}Û­ë5Å«3fÏ·«ù¬2: 4¦³õ³Kñ§­Ç¿Ò¾Ù‚Æ\\ÞÆ¾ò»:‹?X°¯ÈÒælÇl\Zîçþ\rl>#ûfŒ©Ï)Ÿ$pY:ó˜kLgQø1’ùÏ&ø\Z+Id‹[OE¢;}\'³¹Òì÷u\\¼ñÉhÇË4rý´3\0’„&“u©rhEÉœß#éE©zªFÂ6ÑŸŠ.b>@Ã¦åò`úå¦¼™\0£Õ½\Zw \\ª¸ÈQ{oCÉndvV¹¤ùhûu*Pâ¥uoWNè¦J‘àŠÌ\\™IL¼ÒM.A¤Xí¼ò…+zõ‰ŸÒp/}G2;¾\Z§^j/fEá?`	ùo:MÃ	›‹§tQ\'&K>#Ô”zPÎ˜½Ð\0\"ú\\u·þwÑæ..Œ]u u††©j=-tÖ\"u‚y\Z/ƒ‘—Pc‡»ÜÍ(‡eÓµ¾+xüañÐ|eLMw\'©µËçÏÉŸ4yV±/WïÙ-¡è\ZE“b#t½uUtóed]z|œXmwrŸª…ÁÉ|¡qòYÈZñ¯¾òE¦GäÁã„GÚyNŽb¹Íš&oÒ¯³±ÊÚmÈ UH|4ðŽ¢ôšlIQV-<B7À¸B\rÇÍÿPVE\'ÁsCæZØŽ\"ƒ’\rêØ^”i\rˆü¸±¤ÍöpÊX†fºÑ$Z2|•	‰~]Yûí,ó+äpúIà31´øŒ2Õmxq5“7²Gú­e`ÀxÛßÂå/Íä-O,êÐ\r>DÁ–Éùš4T´)_`÷ÿ½?62C«†{£°‰€œ6ªnðgÈ¥=µÖ:$×Ô/ó¯§»šj0arøÂõM\"|cÒVlBºœ\\Ì\0RÈé«ErF\rrÀ³üz”ýÄÙBQŸªC3…I’8è¾üÀÜŠŠÍÔÐêì×Ç5fÜª\rf¼P…»mC%eUÐŸŒÍø:…Ž/$ŒntÉt!gu¾257éÂ}@÷Ãƒ€\ZŠ\nþ31‚ò\n$e…†UmrÛ°þ>®ÓµIï_ q€Ë¿¸’¦Wwîü¶Ü«Óð ƒA‚@ Ú%V²æW­:Ø\'i‹øËù¸ÔmÖ!«£UÑi”Lø†6G\Z,èlƒÁø#µž×¦Ã–ï·\'Ð7cýà@÷’ü‡®{ÄÕù²Íuëo*óQÖÐ$ôÕ•àÌŽ¬`,”rp)G]EÈPýþu`ÇøCC5á©ý6•\nc•ÏUñ\"Žh7¨Qž°×Ä²ÖI›HªRWeÇ[ÿä/µè5‚ ôòÜ¸$Sôgi¯éLìÂ2Ty¸WAXî¹u\rö0´ï‘”áÛÈ\0p°=Mˆä\"-’ù¿¨\Z— ÏFÞ’\rÌ1krõŸ•ÿ|Ø,e­\\X­¡fèñð‡‡G÷ ã’ÃßÔ,!úL»q‰ªÖ“ibÂZëŸhÜÙæ×Tx-¤{GVtÈ~°få°À=O²ÿFM;GRì®6ž+Â™1wmTÍ¶±3«=R¿IaŽ>ÿFÚ–ª5rß³¨è•%o }â]ÌYÆSWìx*KW ¦ìßLa¶ z¶å!ºŠ2D²±¡…Y†p¶·U‡•;²¯#ã”Óx\rýÐZ)÷ñ^§ITO¼_A/Ý\ZýØœU­l#ÔßòËœ`x°ü6?odp\\dõJÓI´Ò— IôŠ¶4J;Íz||›$oº:pVi³\"é{¾q‰áÚq–ËMoÆ“¡ê¨tÊãrÈbd®ð?Óp\r<#|sVß0í4%úf·ëXšçßAŒTññÚŸì)þeô\"„Ù\ZÁ|ª‡]rd<hŸNÄD5çZ²ÑÃa%a²ÙlþÊ—[pÓ\'I‹B¶X‰„ûf¬~l6Óí‚G ðèV™-#ÜA,–×l±’¾Ðô¸\\â0»Uõ1%ólÆû·x\Z®èÎŒZÕ¥fÓ§Äim«Ð$Rùï¼`e[ë—fMímU‘#GýöEªw”K\"\nöŸÕ$2ìÊåÉýë—’¿oñ‘Qi‘¾+ÀÙç—sZ#£¶Œà7 s‰ðèë‰óy_7™ÐDÈÁ|\\÷â³»$êê@ÜÐw˜•OÆ¶Å›æh¥j»â²4¸³À;Õò;9$\r#œxÄ˜Aò•úªkâ2o~½Ôšî¤Ðµ+îá•–nz‡/þÝ³]\rïKŠÝ/\r•t!füuÒÔÿí¦èÓUD>¹9’,k}-ã>Ž0š¿Hgçˆ™nÉòÿòë1.ÕtðXggÁ‹ >7¿Ö/mF›B£¹³ywúÑŒa°‰ØI€‰OAöÚ~~¦éH7¾’¹Yã|•ëËwßC‡P~™&þbü\Z\\ ÃˆPÌaQÆINÅÎ-*b-ß=ß¢FÐ°;!ÑÌäŸÈ…€6‰\ZÖ‰?«ëcG“ÿÞ~t˜l;Ëg%¯H7ˆî”ÿu–Ã^ñ–´B\r5PsKŸS¡\nÍ’VWpŸðoIbjèòå1­ëö‹Æí?VoI\0E‘]—[vÃu°ó¯u¾õmÎfå!|¤žä\\lRÏGO‚K1½¦)~ýÌ\rK$•ÜòÄÅÕÏ–¦7oÒ	A°DZ	2KÌSx\"¬¬kto2{„3üÌ.‰|/úðæÅf9‚ç±þ‹¨†þ­§éºÜn9ò‹¬eÄ=4Ì-†µ­‡PLÅj^¹ž«', 'db999ll111666ddmmm333ffwwwyyyttt'),
('ðœ¨ÇªZ³VB!üÆ8þ®¨', 'i»EŠ©vl?vðZ,UµRì €…öÈ`zñ,)T6', '2u8n59po84g2vvhopcg1evnr8g'),
('…÷&þ¡½Ú™a w‡', 'T£ß£¯SÏ ‰„˜…)IÐ=*[JùEvFª;ˆ}', '2u8n59po84g2vvhopcg1evnr8g'),
('[ä ó¾éF¼¥[ò“M	-Œ', 'JÀ{9(ë#‘õ…‡D ¢	', '2u8n59po84g2vvhopcg1evnr8g'),
('äúfæ\Z\0G´üùUB', 'ö÷[æ­;ÌXôû”}ïvmå¨t^YÍ¶µž4ä—®‘Ze', '2u8n59po84g2vvhopcg1evnr8g'),
('^Á3\0è×fø\0[.UN', '—T›¦Š+Áº˜çðcAuff{Ãc@©|IèÑÙñÊ¯ò½—Ó°Ä‰ç¸1×d¼øøÖaÖ9’38žeéJ<D<€’\0ëë·mPý¥Í¡•', '2u8n59po84g2vvhopcg1evnr8g'),
('ˆe`òuéUfõêcrOú', '‹ ›.¦v§Q\n:Ø	Ê', '2u8n59po84g2vvhopcg1evnr8g'),
('ïíÓê1%ådþ½ù;¦', 'udí¿kèÿ¹4®¡³j', '2u8n59po84g2vvhopcg1evnr8g'),
('#]N„S‰Ó\\	Ð» ×', 'SÊË@hu‰JQÇå«žL–ÜX;±²Ó4ªü¾¡+uM¬t\'šú³\rOwxDY6S³¥±Ûà¿è1˜t{þ¢¶\0ô³ï×X¡u”Ó.dç%^ Âã4NgÄ(ð-™…=û}_‘lrúšg©ºMqŸ', '2u8n59po84g2vvhopcg1evnr8g'),
('ó}ß}ª“åHngåål', '¯\rÖþŽf¹¸¼4Ò¡Bµ±¨`„û2DvñKp&Š—bÙß§u™LÉ|IŽmÕ9™üu¡¶ÛÈï8¿5îù†›”¤+4ŽÓVöb„X¹u.ôq.öôû£ÂÒÑ’ef¨ÓÑ´X¤4žó-¡Ø¯Çä%c;E<–3>‚tæ{BU\rAdN·‡C+¹Àxëô‚ØGán²éûÕAVOèoGIýæäŸ¯ú·¢²NõŸEE«K¢˜Í6_\r¹z:µÌ„½cõ¸3k¶)±GÔ¼`ãµ€#©0€j¥2ùìÆø˜Ñ3—qEÌË-¹5¸|˜-÷‰Ü?Iî °øXô™œ]kf ”nç›lp=UcºA,þÂ,ñíô‡©â^iñDd¬“‰M?‡œú½jkÔ3ð%î1XÅØ;|l|Ú½·eËØèÚO„_=jÕd­¥8?|2Z«¼“¬û»’“Óœ°Ðpš=Þ?ËÉ7«V¯-ßé†h,.Æ;¦ù˜	.»ýÍ´N{u—øª¨%s³#”¸ïU7(7œ^øD6úš•\Zâµ ˜<{†t½Š\'´ÛÐRñ­ìqñêöÄ1Õ$º…vx÷ŽlªljbkdYÉðu§,í;×&êáÙÇ\Z,Õ Ôd\0éOTÌD‘Œ»­â¸”O\0/ÿ¹ñ¿_›žÃ.¸P³P“f°öWñá4ã¤˜f¡È>,^GÛÃ]ˆÇõGLçfB”’{“RIÅ‘b«*(:pOº¾÷÷¶ãÞ˜*v`\\î²N)Ý\r—Çã]•Ç>÷%º °®*‘Pk^ayµ#o@qËà¶Š=gA2«X½Ád4€sd\\TØÇeRMuº÷ÿfp¥ŽÏódW+#-úÁ3ôÓyq•²j†1<Ÿ¬Ø0È¨–spiõL‚d&-7ux|—à½@GDež†Uh{ô:ø\0\'~®\0ß‹;ß$l\n“4†ñâû<W#Õ¢Å[Ø-4K6£<Q\nÙøHé¶%-¬EµŽ… çÕ½	TÍËDkÉ™X5y!Ý¥DÝ!_\"ói[¾§6}oÁ}IM¹2cÉÔ—zMæQ÷7‹êŽÅ”­ÖRô¾Á;ýM¢¹Áe HX%sfo²ÿÌ?á\r.mþ»Ýn~!êËŠl¹·þ†3‘fÁ˜°’Ã½ïÓ}ÜQ7OWr³}u\'1%ê	`«Àø¼µ}ÕÖÛý[üÏ}“!%»C‡”5Qú)_Ð€–6ƒÉtú\'gÒkMuˆ”€ˆ\"Ëd8/ys¢tÒÈšÐ§Ç¸OTƒÃDh×Pè¬Æ5Qõ°e8c«Ÿ„FÍºÆÆ©”e¦º6c:èÕ?ñ1Q©¶ìÝ,•§ãˆþ¡kYÐµC=5S¶0+Þ!æÓÔâÄ‘u˜Ï•š] >²ÿEÌ âToïht»—3Ýƒ<7ÎâðÒ–7S;ïõ‹Ïm!Ø>êCËÐ1´ºÕ7Åàxµt4;¦âlZ±´J•ƒÕûBŠ©è,‘VRªdUÏ–†ñ\\]5g0<Û&ºØÈìî*Ki›R•û\0QÆ„ÙPÒjþ¨Û/hºó}~Àaaìîá&ò™ã:ºÿÓœç•¤Æ9 ­ˆŽu»“<øŒ–¡o¾ëò%®­âá±B[®µ,>¢>4-Wb¼x4\rçÙÄa«—½	T\Zœs[òñM¸1/µ¯Íµø²¼¬ùƒ—j¨@º—ÊçÑu÷<Ì~Å¶Pù0býÁÜžL,I)Ä[¦¼#Øp\\>Kœ‚Ûcõö¬šn§‘ñß7ÏsùÂÕ’ÔÀèh‚aUZ¨>\'°Ó-TQ€Üýå™I)³Á^S°HKœ—ä´¹ƒâÐö’ãÎI¹P\"mäÎ\'+aP «d6ÝRó%Ô=#›KSk U÷ièŸðiÅ&±20&ßÒVh1À#naa¨QdŸà1h˜û	­ÚôwÜÌÖN¦ñ>?4.šˆ)¿¶}Ã­EXðT®“¾¡1TZ™oeH‡FmNÐÛ‡\"5pŽP|ÓÄ¢à²C Ž;¨[w ;¦ÜÛ)ãÇ6jßê¬ÞèLNßöÂÓ˜fPô1)ˆæh†\0‰¡(¬píÛñÞë…Å.ÂÙ¯É²&É Áó=çÐ9[eºmùqÂŠÄ‹-žŒÜ÷G™;5IpZÄ¸h_°Pÿ”¶OŽ Î.ÈãÊðÂªõ‹¼Œûˆ\Z£Îã:]ÍD»•¨}…:uðŠ¾‹÷jKz„¤^\rïÀF—nÙp7!\nª6 _>-ô*ï¯iæô š=µií‚VOÉâ-ìàç³€œNyÀZzt³oÁE^ˆ_JÜB—‹ÉÝgË›´WÕT±„¾ø6ç‘E¢÷˜DøU«å•RWáà>²#’Ü_¾,!ðèŠ^swmš–¯‘=¹y¥ëñ†bƒ¸\r{mWóšsÿ;e6¶µK!Â¢•}(…ù\Z8A£ ÈöÀ„K-2æ‰E‘V{kl‚™¾>œ˜ú«F!€¡0?¿½„¤^\rïÀF—nÙp7!\nªö…¯ù²½ºÛÿU¸+1Ö?\'J–³³÷\' F•÷¼Õn	)ý6ôPSèÀµà4¡ä.pQÁ‰ÏÿœÔõÀÁ{ÐŒÝEÝðÏPzó)ä%†äy°2‰î	û#ôeP[Ì¾öI^€U×»¦@¯‘bï	8Šgqï£Á%ÿí4”Î_H`mvÇÑ÷î±Ç·Y»¾(vSk^»7%ÿ¾GŠGZìF–¬çû×Ú†ÃE ãàõt3#ÉnË‡åª\Z÷B@:ŠÖëÛG¾Ê\Z‡«æWTžck‹’{M|a—ßÆ¨ÕÀÔ\ZwGzþD6\"GˆÍ0—>ã=øð«÷ha=¼‚ô|rY8×fÂÂráF‚œÅ›#ÚÚ¯ï(µ=ì¦ŸõTL/6E\Z{H\"Bpp=>KÎ$ÃVæÈ#ÿ†ª#î‚;t	ÞB£c\"ë„jâ½‚%fžÈÔ<¨FTovÜ˜Éaò¶n_	¯‹Ýz;ÞÿO	è;ái©ÙÎ/°º­|…Ôçg7Pä¿V¢š]•ŽC\nŒéMç¤7’`¹@_V2whÆU®›¿Ü°º©…~¨^Çz¡B|ãÇìNÌ(tÕl“Áö¦)ˆh3BdH>}IÊ¿?á4©ÇXn[‰š˜¬ŒeÍé<àEæãì(ûaGrÏr¢Œ„­F/ðñkû®¾T^#\rœðÑ¿Ð‹‘\ZçD`XÉèèã÷	ƒ_9Oü’5Áº£üVÀ/ŽðÈÇl†*\Z7ñ7—îFbn6uWC±ØÏÄ<wÙ/ìf.sÞ+q:€¯újä¢tmÑR6ózaêFx‘—ÿ@fr…¥´y§!ÞÄ—zŽ3Óv•Ãôð—æ}ÿQ!;Å^ü©ÑøÒ.\\T~èP Õÿ^LYû—àd/‹™ÕOò\\¶¨JãÚ!wkñ¯`;ÑÃü(I/`ªjy\rÉÂ§«dÛå,ôvf»¯6\r\Zs9© ðdÅ›æh¥j»â²4¸³À;Õò;9$\r#œxÄ˜Aò•úªkâ2o~½Ôšî¤Ðµ+îá•–nz‡/þÝ³]\rïKŠÝ/\r•t!füuÒÔÿí¦èÓUD>¹9’,k}-ã>Ž0š¿Hgçˆ™nÉòÿòë1.ÕtðXggÁ‹ >7¿Ö/mF›B£¹³ywúÑŒa°‰ØI€‰OAöÚ~~¦éH7¾’¹Yã|•ëËwßC‡P~™&þbü\Z\\ ÃˆPÌaQÆINÅÎ-*b-ß\"ËqšE[\Z¯ƒ\rJq³UqDÅ;ò%óf{ ˜ÜaðßG\0íÒbàœúaNÂx½òIÖÈ9Vº\Z\Z×®n–—3ü®R,J\\‘ÕU hnéÔ´+j}ªrYšt<°vö}YF¨‡¨õl¤}„=Kµp–£ãœ.ß&”õãEB¬=£M#¬®*XÈü ÃÔAq&!7‰à>«ê2^XY0³Ž$g¾ÆUã]“{Ä!™ª*_|qãy50éGö©–åÞ\Z¼þw´A§@p¼~°[ô÷[ëÐbŒ³', '2u8n59po84g2vvhopcg1evnr8g');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sis_tipo_tel`
--

CREATE TABLE `sis_tipo_tel` (
  `id_tipo_tel` varchar(1) NOT NULL DEFAULT '',
  `desc_tipo_tel` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Volcado de datos para la tabla `sis_tipo_tel`
--

INSERT INTO `sis_tipo_tel` (`id_tipo_tel`, `desc_tipo_tel`) VALUES
('C', 'Casa'),
('F', 'Fax'),
('M', 'Movil'),
('T', 'Trabajo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sis_user`
--

CREATE TABLE `sis_user` (
  `id` varchar(50) NOT NULL,
  `nombre` varchar(150) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `telefono` varchar(15) DEFAULT NULL,
  `id_tipo_tel` varchar(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Volcado de datos para la tabla `sis_user`
--

INSERT INTO `sis_user` (`id`, `nombre`, `email`, `telefono`, `id_tipo_tel`) VALUES
('105710421', 'GEORGES ALFARO SALAZAR', 'georges.alfaro.salazar@una.cr', NULL, NULL),
('107010122', 'GUISELLE VÍQUEZ JIMÉNEZ', 'guiselle.viquez@gmail.com', '83263459', 'M'),
('110600492', 'MAIKOL GUZMÁN ALÁN', 'maikol.guzman.alan@una.cr', NULL, NULL),
('111710169', 'MIGUEL ARTURO CORRALES UREÑA', 'miguel.corrales.urena@una.cr', '25626364', 'T'),
('116440018', 'MARCO ANTONIO MURILLO SÁNCHEZ', 'mmurillo532@gmail.com', NULL, NULL),
('118440202', 'LARISSA SEGURA ARGUELLO', 'larissa.segura.arguello@est.una.ac.cr', NULL, NULL),
('205610158', 'OSCAR CHAVES BARRANTES', 'oscar.chaves.barrantes@una.cr', '25626370', 'T'),
('205830110', 'KATTY VÁSQUEZ ÁVILA', 'katty.vasquez.avila@una.cr', '88198417', 'M'),
('206580363', 'MIGUEL DÍAZ GUTIÉRREZ', 'miguel.diaz.gutierrez@est.una.ac.cr', '84484757', 'M'),
('402290345', 'ESTEBAN ESPINOZA FALLAS', 'eef251195@gmail.com', NULL, NULL),
('503020651', 'CARLOS LUIS CHANTO ESPINOZA', 'carlos.chanto.espinoza@una.ac.cr', '83429147', 'M'),
('503230754', 'EDDIER LÓPEZ LÓPEZ', 'eddier.lopez.lopez@una.ac.cr', NULL, NULL),
('503550224', 'MIGUEL ÁNGEL RODRÍGUEZ ARIAS', 'miguel.rodriguez.arias@est.una.ac.cr', '84281699', 'M'),
('504410118', 'CARLOS DANIEL LÓPEZ CHÉVEZ', 'carlos.lopez.chevez@est.una.ac.cr', NULL, NULL),
('504430777', 'JOSE DOMINGO MOLINA SALAS', 'jose.molina.salas@est.una.ac.cr', NULL, NULL),
('701810347', 'JONATHAN  MANRIQUE CORDERO  DUARTE', 'jcordero1987@gmail.com', '88595127', 'M'),
('800810596', 'YAMILETH HERNANDEZ CANO', 'yamileth.hernandez.cano@una.ac.cr', '25626367', 'T'),
('800870458', 'DARINKA GRBIC GRBIC', 'darinka.grbic.grbic@una.cr', '88373584', 'M');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tfg_document_reviews`
--

CREATE TABLE `tfg_document_reviews` (
  `id` int(11) NOT NULL,
  `document_id` int(11) NOT NULL COMMENT 'FK a tfg_final_documents',
  `file_version` int(11) NOT NULL COMMENT 'Versión del archivo revisado',
  `reviewer_id` varchar(50) DEFAULT NULL COMMENT 'ID del revisor (CTFG), NULL si es corrección del estudiante',
  `review_type` enum('Revision CTFG','Correccion Estudiante') NOT NULL COMMENT 'Tipo de revisión',
  `status` enum('Aprobado','Rechazado','Pendiente de Revision') NOT NULL COMMENT 'Estado de la revisión',
  `corrections_summary` text DEFAULT NULL COMMENT 'Resumen de correcciones solicitadas o realizadas',
  `corrections_count` int(11) DEFAULT 0 COMMENT 'Contador de ciclos de corrección',
  `reviewed_at` datetime DEFAULT current_timestamp() COMMENT 'Fecha de la revisión'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tfg_extension_requests`
--

CREATE TABLE `tfg_extension_requests` (
  `id` int(11) NOT NULL,
  `proposal_id` int(11) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `extension_number` tinyint(4) NOT NULL COMMENT '1=Primera prórroga (1 año), 2=Segunda prórroga (6 meses)',
  `reason` text NOT NULL,
  `status` enum('pendiente','aprobada','rechazada') DEFAULT 'pendiente',
  `request_date` datetime DEFAULT current_timestamp(),
  `response_date` datetime DEFAULT NULL,
  `responded_by` varchar(50) DEFAULT NULL,
  `response_comment` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tfg_files`
--

CREATE TABLE `tfg_files` (
  `id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `file_size` bigint(20) NOT NULL,
  `file_data` longblob NOT NULL,
  `storage_path` varchar(500) DEFAULT NULL,
  `uploaded_by` varchar(50) NOT NULL,
  `upload_date` datetime DEFAULT current_timestamp(),
  `version` float NOT NULL DEFAULT 1,
  `document_type` varchar(50) NOT NULL,
  `proposal_id` int(11) DEFAULT NULL COMMENT 'FK a tfg_proposals (archivo de propuesta)',
  `final_document_id` int(11) DEFAULT NULL COMMENT 'FK a tfg_final_documents (archivo de documento final)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tfg_files_archive`
--

CREATE TABLE `tfg_files_archive` (
  `id` int(11) NOT NULL,
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
  `archived_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tfg_final_documents`
--

CREATE TABLE `tfg_final_documents` (
  `id` int(11) NOT NULL,
  `proposal_id` int(11) NOT NULL,
  `file_id` int(11) NOT NULL,
  `status` enum('Pendiente de Revision','En Revision','Aprobado','Rechazado') DEFAULT 'Pendiente de Revision',
  `project_status` enum('Vigente','Prorroga Activa','Vencido') NOT NULL,
  `submitted_at` datetime DEFAULT current_timestamp(),
  `submitted_by` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tfg_notifications`
--

CREATE TABLE `tfg_notifications` (
  `id` int(11) NOT NULL,
  `notification_type` enum('Documento Final Subido') NOT NULL,
  `proposal_id` int(11) NOT NULL,
  `sender_id` varchar(50) NOT NULL,
  `recipient_role_id` int(11) NOT NULL COMMENT '3 = CTFG',
  `message` text NOT NULL,
  `status` enum('Pendiente','Enviada') DEFAULT 'Pendiente',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tfg_project_timeline`
--

CREATE TABLE `tfg_project_timeline` (
  `id` int(11) NOT NULL,
  `proposal_id` int(11) NOT NULL,
  `approval_date` date NOT NULL,
  `original_deadline` date NOT NULL COMMENT '1 año (12 meses) desde aprobación',
  `status` enum('Vigente','Prorroga Activa','Vencido') NOT NULL DEFAULT 'Vigente',
  `days_remaining` int(11) DEFAULT NULL,
  `last_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tfg_proposals`
--

CREATE TABLE `tfg_proposals` (
  `id` int(11) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `disciplines` varchar(255) NOT NULL,
  `project_description` text DEFAULT NULL,
  `document` longblob DEFAULT NULL,
  `file_name` varchar(255) NOT NULL DEFAULT '',
  `mime_type` varchar(100) NOT NULL DEFAULT '',
  `file_size` int(11) NOT NULL DEFAULT 0,
  `status` enum('Pendiente de Revision','Cumple requisitos','No cumple requisitos','Aprobado','Rechazado') DEFAULT 'Pendiente de Revision',
  `admin_comments` text DEFAULT NULL,
  `reviewed_by` varchar(50) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tfg_proposals_archive`
--

CREATE TABLE `tfg_proposals_archive` (
  `id` int(11) NOT NULL,
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
  `archived_at` datetime DEFAULT current_timestamp() COMMENT 'Fecha de archivado',
  `archived_by` varchar(50) DEFAULT NULL COMMENT 'Usuario que archivó (NULL si automático)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tfg_proposal_history`
--

CREATE TABLE `tfg_proposal_history` (
  `id` int(11) NOT NULL,
  `proposal_id` int(11) NOT NULL,
  `document` longblob NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `file_size` int(10) UNSIGNED NOT NULL,
  `status` varchar(50) NOT NULL,
  `reviewed_by` varchar(50) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `user_alerts`
--

CREATE TABLE `user_alerts` (
  `id` int(11) NOT NULL,
  `user_id` varchar(50) NOT NULL COMMENT 'Usuario destinatario de la alerta',
  `subject` varchar(255) NOT NULL COMMENT 'Asunto de la alerta',
  `message` text NOT NULL COMMENT 'Mensaje detallado',
  `alert_type` enum('Nueva Propuesta','Propuesta Aprobada','Propuesta Rechazada','Documento Final','Correccion Solicitada','Asesor Aprobado','Asesor Rechazado','Prorroga','Informativa','Sistema') NOT NULL DEFAULT 'Informativa' COMMENT 'Tipo de alerta',
  `priority` enum('Alta','Media','Baja') DEFAULT 'Media' COMMENT 'Prioridad de la alerta',
  `related_entity_type` varchar(50) DEFAULT NULL COMMENT 'Tipo de entidad relacionada (proposal, document, etc.)',
  `related_entity_id` int(11) DEFAULT NULL COMMENT 'ID de la entidad relacionada',
  `read_at` datetime DEFAULT NULL COMMENT 'Fecha/hora en que se leyó la alerta',
  `sent_at` datetime DEFAULT current_timestamp() COMMENT 'Fecha/hora de envío'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='HU-037: Alertas internas del sistema';

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vis_user`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vis_user` (
`id` varchar(50)
,`nombre` varchar(150)
,`roll` varchar(100)
);

-- --------------------------------------------------------

--
-- Estructura para la vista `vis_user`
--
DROP TABLE IF EXISTS `vis_user`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vis_user`  AS SELECT `a`.`id` AS `id`, `b`.`nombre` AS `nombre`, (select `sis_rolls`.`roll_name` from `sis_rolls` where `sis_rolls`.`id_roll` = `a`.`id_roll`) AS `roll` FROM (`sis_login` `a` join `sis_user` `b`) WHERE `a`.`id` = `b`.`id` ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `acuerdo_cancelacion`
--
ALTER TABLE `acuerdo_cancelacion`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_cancelacion_proyecto` (`proyecto_id`);

--
-- Indices de la tabla `archive_audit_log`
--
ALTER TABLE `archive_audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action` (`action_type`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`idCategoria`);

--
-- Indices de la tabla `comite`
--
ALTER TABLE `comite`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `idx_tutor` (`tutor`),
  ADD KEY `idx_asesor_1` (`asesor_1`),
  ADD KEY `idx_asesor_2` (`asesor_2`);

--
-- Indices de la tabla `external_advisor_linked_students`
--
ALTER TABLE `external_advisor_linked_students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_advisor_student` (`advisor_request_id`,`student_id`),
  ADD KEY `idx_advisor_request` (`advisor_request_id`),
  ADD KEY `idx_student` (`student_id`),
  ADD KEY `idx_project` (`project_id`),
  ADD KEY `idx_eal_lookup` (`advisor_request_id`,`student_id`,`is_primary`);

--
-- Indices de la tabla `external_advisor_profile_requests`
--
ALTER TABLE `external_advisor_profile_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_external_advisor_request_applicant` (`applicant_id`),
  ADD KEY `idx_external_advisor_request_status` (`status`),
  ADD KEY `idx_approval_expires` (`approval_expires_at`),
  ADD KEY `idx_linked_proposal` (`linked_proposal_id`),
  ADD KEY `fk_external_advisor_request_reviewer` (`reviewed_by`);

--
-- Indices de la tabla `project_history`
--
ALTER TABLE `project_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_project_id` (`project_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action` (`action_type`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indices de la tabla `project_members`
--
ALTER TABLE `project_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_project_user` (`project_id`,`user_id`),
  ADD KEY `idx_project_id` (`project_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_status` (`status`);

--
-- Indices de la tabla `project_members_archive`
--
ALTER TABLE `project_members_archive`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_original_project` (`original_project_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indices de la tabla `project_types`
--
ALTER TABLE `project_types`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `proyecto_aprobado`
--
ALTER TABLE `proyecto_aprobado`
  ADD PRIMARY KEY (`id_aprobado`),
  ADD UNIQUE KEY `uq_identificador` (`identificador`),
  ADD KEY `idx_comite_id` (`comite_id`),
  ADD KEY `idx_proposal_id` (`proposal_id`);

--
-- Indices de la tabla `proyecto_aprobado_estudiantes`
--
ALTER TABLE `proyecto_aprobado_estudiantes`
  ADD PRIMARY KEY (`id_aprobado`,`estudiante_id`),
  ADD KEY `fk_pae_estudiante` (`estudiante_id`);

--
-- Indices de la tabla `proyecto_notas`
--
ALTER TABLE `proyecto_notas`
  ADD KEY `proyecto_id` (`proyecto_id`);

--
-- Indices de la tabla `registered_projects`
--
ALTER TABLE `registered_projects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_tfg_project` (`tfg_proposal_id`),
  ADD KEY `idx_tfg_proposal` (`tfg_proposal_id`),
  ADD KEY `idx_project_type` (`project_type_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_supervisor` (`supervisor_id`);

--
-- Indices de la tabla `registered_projects_archive`
--
ALTER TABLE `registered_projects_archive`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_original_project` (`original_project_id`),
  ADD KEY `idx_original_proposal` (`original_proposal_id`),
  ADD KEY `idx_archive_reason` (`archive_reason`);

--
-- Indices de la tabla `sis_log`
--
ALTER TABLE `sis_log`
  ADD PRIMARY KEY (`id_bi`);

--
-- Indices de la tabla `sis_login`
--
ALTER TABLE `sis_login`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_roll_user` (`id_roll`);

--
-- Indices de la tabla `sis_mod`
--
ALTER TABLE `sis_mod`
  ADD PRIMARY KEY (`id_mod`);

--
-- Indices de la tabla `sis_mod_actions`
--
ALTER TABLE `sis_mod_actions`
  ADD PRIMARY KEY (`id_action`);

--
-- Indices de la tabla `sis_parametros_varios`
--
ALTER TABLE `sis_parametros_varios`
  ADD PRIMARY KEY (`id_pv`);

--
-- Indices de la tabla `sis_permits`
--
ALTER TABLE `sis_permits`
  ADD PRIMARY KEY (`id_permit`),
  ADD KEY `fk_mod` (`id_mod`),
  ADD KEY `fk_mod_action` (`id_action`),
  ADD KEY `fk_roll` (`id_roll`);

--
-- Indices de la tabla `sis_rolls`
--
ALTER TABLE `sis_rolls`
  ADD PRIMARY KEY (`id_roll`);

--
-- Indices de la tabla `sis_sessions`
--
ALTER TABLE `sis_sessions`
  ADD PRIMARY KEY (`sid`);

--
-- Indices de la tabla `sis_sessions_vars`
--
ALTER TABLE `sis_sessions_vars`
  ADD KEY `sid` (`sid`);

--
-- Indices de la tabla `sis_tipo_tel`
--
ALTER TABLE `sis_tipo_tel`
  ADD PRIMARY KEY (`id_tipo_tel`);

--
-- Indices de la tabla `sis_user`
--
ALTER TABLE `sis_user`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `tfg_document_reviews`
--
ALTER TABLE `tfg_document_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_document_id` (`document_id`),
  ADD KEY `idx_reviewer_id` (`reviewer_id`),
  ADD KEY `idx_review_type` (`review_type`),
  ADD KEY `idx_reviewed_at` (`reviewed_at`);

--
-- Indices de la tabla `tfg_extension_requests`
--
ALTER TABLE `tfg_extension_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_proposal` (`proposal_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indices de la tabla `tfg_files`
--
ALTER TABLE `tfg_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_upload_date` (`upload_date`),
  ADD KEY `fk_tfg_files_user` (`uploaded_by`),
  ADD KEY `idx_tfg_files_proposal` (`proposal_id`),
  ADD KEY `idx_tfg_files_final_document` (`final_document_id`);

--
-- Indices de la tabla `tfg_files_archive`
--
ALTER TABLE `tfg_files_archive`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_original_file` (`original_file_id`),
  ADD KEY `idx_original_proposal` (`original_proposal_id`);

--
-- Indices de la tabla `tfg_final_documents`
--
ALTER TABLE `tfg_final_documents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_proposal_final` (`proposal_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_submitted_at` (`submitted_at`),
  ADD KEY `fk_tfg_final_file` (`file_id`),
  ADD KEY `fk_tfg_final_submitter` (`submitted_by`);

--
-- Indices de la tabla `tfg_notifications`
--
ALTER TABLE `tfg_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_recipient` (`recipient_role_id`,`status`),
  ADD KEY `fk_tfg_notif_proposal` (`proposal_id`),
  ADD KEY `fk_tfg_notif_sender` (`sender_id`);

--
-- Indices de la tabla `tfg_project_timeline`
--
ALTER TABLE `tfg_project_timeline`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_proposal_timeline` (`proposal_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indices de la tabla `tfg_proposals`
--
ALTER TABLE `tfg_proposals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_title` (`title`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indices de la tabla `tfg_proposals_archive`
--
ALTER TABLE `tfg_proposals_archive`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_original_proposal` (`original_proposal_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_archive_reason` (`archive_reason`),
  ADD KEY `idx_archived_at` (`archived_at`);

--
-- Indices de la tabla `tfg_proposal_history`
--
ALTER TABLE `tfg_proposal_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `proposal_id` (`proposal_id`),
  ADD KEY `reviewed_by` (`reviewed_by`);

--
-- Indices de la tabla `user_alerts`
--
ALTER TABLE `user_alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_alert_type` (`alert_type`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_read_at` (`read_at`),
  ADD KEY `idx_sent_at` (`sent_at`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `acuerdo_cancelacion`
--
ALTER TABLE `acuerdo_cancelacion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `archive_audit_log`
--
ALTER TABLE `archive_audit_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `comite`
--
ALTER TABLE `comite`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `external_advisor_linked_students`
--
ALTER TABLE `external_advisor_linked_students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `external_advisor_profile_requests`
--
ALTER TABLE `external_advisor_profile_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `project_history`
--
ALTER TABLE `project_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `project_members`
--
ALTER TABLE `project_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `project_members_archive`
--
ALTER TABLE `project_members_archive`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `project_types`
--
ALTER TABLE `project_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `proyecto_aprobado`
--
ALTER TABLE `proyecto_aprobado`
  MODIFY `id_aprobado` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `registered_projects`
--
ALTER TABLE `registered_projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `registered_projects_archive`
--
ALTER TABLE `registered_projects_archive`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `sis_log`
--
ALTER TABLE `sis_log`
  MODIFY `id_bi` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identificador para bitacora';

--
-- AUTO_INCREMENT de la tabla `sis_mod`
--
ALTER TABLE `sis_mod`
  MODIFY `id_mod` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `sis_mod_actions`
--
ALTER TABLE `sis_mod_actions`
  MODIFY `id_action` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `sis_parametros_varios`
--
ALTER TABLE `sis_parametros_varios`
  MODIFY `id_pv` int(16) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `sis_permits`
--
ALTER TABLE `sis_permits`
  MODIFY `id_permit` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=189;

--
-- AUTO_INCREMENT de la tabla `sis_rolls`
--
ALTER TABLE `sis_rolls`
  MODIFY `id_roll` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `tfg_document_reviews`
--
ALTER TABLE `tfg_document_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tfg_extension_requests`
--
ALTER TABLE `tfg_extension_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tfg_files`
--
ALTER TABLE `tfg_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tfg_files_archive`
--
ALTER TABLE `tfg_files_archive`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tfg_final_documents`
--
ALTER TABLE `tfg_final_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tfg_notifications`
--
ALTER TABLE `tfg_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tfg_project_timeline`
--
ALTER TABLE `tfg_project_timeline`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tfg_proposals`
--
ALTER TABLE `tfg_proposals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tfg_proposals_archive`
--
ALTER TABLE `tfg_proposals_archive`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tfg_proposal_history`
--
ALTER TABLE `tfg_proposal_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `user_alerts`
--
ALTER TABLE `user_alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `acuerdo_cancelacion`
--
ALTER TABLE `acuerdo_cancelacion`
  ADD CONSTRAINT `fk_cancelacion_proyecto` FOREIGN KEY (`proyecto_id`) REFERENCES `proyecto_aprobado` (`id_aprobado`) ON DELETE CASCADE;

--
-- Filtros para la tabla `comite`
--
ALTER TABLE `comite`
  ADD CONSTRAINT `fk_comite_asesor1` FOREIGN KEY (`asesor_1`) REFERENCES `sis_user` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_comite_asesor2` FOREIGN KEY (`asesor_2`) REFERENCES `sis_user` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_comite_tutor` FOREIGN KEY (`tutor`) REFERENCES `sis_user` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `external_advisor_linked_students`
--
ALTER TABLE `external_advisor_linked_students`
  ADD CONSTRAINT `fk_eal_advisor_request` FOREIGN KEY (`advisor_request_id`) REFERENCES `external_advisor_profile_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eal_student` FOREIGN KEY (`student_id`) REFERENCES `sis_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `external_advisor_profile_requests`
--
ALTER TABLE `external_advisor_profile_requests`
  ADD CONSTRAINT `fk_external_advisor_request_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `sis_user` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `project_history`
--
ALTER TABLE `project_history`
  ADD CONSTRAINT `fk_project_history_project` FOREIGN KEY (`project_id`) REFERENCES `registered_projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_project_history_user` FOREIGN KEY (`user_id`) REFERENCES `sis_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `project_members`
--
ALTER TABLE `project_members`
  ADD CONSTRAINT `fk_project_members_project` FOREIGN KEY (`project_id`) REFERENCES `registered_projects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_project_members_user` FOREIGN KEY (`user_id`) REFERENCES `sis_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `proyecto_aprobado`
--
ALTER TABLE `proyecto_aprobado`
  ADD CONSTRAINT `fk_proy_comite` FOREIGN KEY (`comite_id`) REFERENCES `comite` (`Id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_proy_proposal` FOREIGN KEY (`proposal_id`) REFERENCES `tfg_proposals` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `proyecto_aprobado_estudiantes`
--
ALTER TABLE `proyecto_aprobado_estudiantes`
  ADD CONSTRAINT `fk_pae_estudiante` FOREIGN KEY (`estudiante_id`) REFERENCES `sis_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pae_proyecto` FOREIGN KEY (`id_aprobado`) REFERENCES `proyecto_aprobado` (`id_aprobado`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `proyecto_notas`
--
ALTER TABLE `proyecto_notas`
  ADD CONSTRAINT `proyecto_notas_ibfk_1` FOREIGN KEY (`proyecto_id`) REFERENCES `proyecto_aprobado` (`id_aprobado`);

--
-- Filtros para la tabla `registered_projects`
--
ALTER TABLE `registered_projects`
  ADD CONSTRAINT `fk_registered_projects_tfg` FOREIGN KEY (`tfg_proposal_id`) REFERENCES `tfg_proposals` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_registered_projects_type` FOREIGN KEY (`project_type_id`) REFERENCES `project_types` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `sis_login`
--
ALTER TABLE `sis_login`
  ADD CONSTRAINT `fk_roll_user` FOREIGN KEY (`id_roll`) REFERENCES `sis_rolls` (`id_roll`);

--
-- Filtros para la tabla `sis_permits`
--
ALTER TABLE `sis_permits`
  ADD CONSTRAINT `fk_mod` FOREIGN KEY (`id_mod`) REFERENCES `sis_mod` (`id_mod`),
  ADD CONSTRAINT `fk_mod_action` FOREIGN KEY (`id_action`) REFERENCES `sis_mod_actions` (`id_action`),
  ADD CONSTRAINT `fk_roll` FOREIGN KEY (`id_roll`) REFERENCES `sis_rolls` (`id_roll`);

--
-- Filtros para la tabla `sis_sessions_vars`
--
ALTER TABLE `sis_sessions_vars`
  ADD CONSTRAINT `sis_sessions_vars_ibfk_1` FOREIGN KEY (`sid`) REFERENCES `sis_sessions` (`sid`) ON DELETE CASCADE;

--
-- Filtros para la tabla `sis_user`
--
ALTER TABLE `sis_user`
  ADD CONSTRAINT `fk_sis_login_sis_user` FOREIGN KEY (`id`) REFERENCES `sis_login` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Filtros para la tabla `tfg_document_reviews`
--
ALTER TABLE `tfg_document_reviews`
  ADD CONSTRAINT `fk_review_document` FOREIGN KEY (`document_id`) REFERENCES `tfg_final_documents` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_review_reviewer` FOREIGN KEY (`reviewer_id`) REFERENCES `sis_user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `tfg_extension_requests`
--
ALTER TABLE `tfg_extension_requests`
  ADD CONSTRAINT `fk_extension_proposal` FOREIGN KEY (`proposal_id`) REFERENCES `tfg_proposals` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `tfg_files`
--
ALTER TABLE `tfg_files`
  ADD CONSTRAINT `fk_tfg_files_final_document` FOREIGN KEY (`final_document_id`) REFERENCES `tfg_final_documents` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tfg_files_proposal` FOREIGN KEY (`proposal_id`) REFERENCES `tfg_proposals` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tfg_files_user` FOREIGN KEY (`uploaded_by`) REFERENCES `sis_user` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `tfg_final_documents`
--
ALTER TABLE `tfg_final_documents`
  ADD CONSTRAINT `fk_tfg_final_file` FOREIGN KEY (`file_id`) REFERENCES `tfg_files` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tfg_final_proposal` FOREIGN KEY (`proposal_id`) REFERENCES `tfg_proposals` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tfg_final_submitter` FOREIGN KEY (`submitted_by`) REFERENCES `sis_user` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `tfg_notifications`
--
ALTER TABLE `tfg_notifications`
  ADD CONSTRAINT `fk_tfg_notif_proposal` FOREIGN KEY (`proposal_id`) REFERENCES `tfg_proposals` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tfg_notif_role` FOREIGN KEY (`recipient_role_id`) REFERENCES `sis_rolls` (`id_roll`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tfg_notif_sender` FOREIGN KEY (`sender_id`) REFERENCES `sis_user` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `tfg_project_timeline`
--
ALTER TABLE `tfg_project_timeline`
  ADD CONSTRAINT `fk_tfg_timeline_proposal` FOREIGN KEY (`proposal_id`) REFERENCES `tfg_proposals` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `tfg_proposals`
--
ALTER TABLE `tfg_proposals`
  ADD CONSTRAINT `fk_tfg_proposals_user` FOREIGN KEY (`user_id`) REFERENCES `sis_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `tfg_proposal_history`
--
ALTER TABLE `tfg_proposal_history`
  ADD CONSTRAINT `tfg_proposal_history_ibfk_1` FOREIGN KEY (`proposal_id`) REFERENCES `tfg_proposals` (`id`),
  ADD CONSTRAINT `tfg_proposal_history_ibfk_2` FOREIGN KEY (`reviewed_by`) REFERENCES `sis_user` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
