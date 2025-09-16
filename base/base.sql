/*
Navicat MySQL Data Transfer

Source Server         : localhost
Source Server Version : 50719
Source Host           : localhost:3306
Source Database       : base

Target Server Type    : MYSQL
Target Server Version : 50719
File Encoding         : 65001

Date: 2025-09-16 09:56:20
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for `sis_provincia`
-- ----------------------------
DROP TABLE IF EXISTS `sis_provincia`;
CREATE TABLE `sis_provincia` (
  `id_prov` varchar(1) NOT NULL DEFAULT '0',
  `desc_prov` varchar(25) DEFAULT NULL,
  PRIMARY KEY (`id_prov`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of sis_provincia
-- ----------------------------
INSERT INTO `sis_provincia` VALUES ('1', 'San José');
INSERT INTO `sis_provincia` VALUES ('2', 'Alajuela');
INSERT INTO `sis_provincia` VALUES ('3', 'Cartago');
INSERT INTO `sis_provincia` VALUES ('4', 'Heredia');
INSERT INTO `sis_provincia` VALUES ('5', 'Guanacaste');
INSERT INTO `sis_provincia` VALUES ('6', 'Puntarenas');
INSERT INTO `sis_provincia` VALUES ('7', 'Limón');

-- ----------------------------
-- Table structure for `sis_canton`
-- ----------------------------
CREATE TABLE `sis_canton` (
  `id_prov` varchar(1) NOT NULL DEFAULT '',
  `id_cant` varchar(2) NOT NULL DEFAULT '',
  `desc_cant` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id_prov`,`id_cant`),
  KEY `fk_id_prov` (`id_prov`),
  KEY `id_cant` (`id_cant`),
  CONSTRAINT `fk_id_prov` FOREIGN KEY (`id_prov`) REFERENCES `sis_provincia` (`id_prov`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of sis_canton
-- ----------------------------
-- Provincia 1: San José
INSERT INTO `sis_canton` VALUES
('1','01','San José'),
('1','02','Escazú'),
('1','03','Desamparados'),
('1','04','Puriscal'),
('1','05','Tarrazú'),
('1','06','Aserrí'),
('1','07','Mora'),
('1','08','Goicoechea'),
('1','09','Santa Ana'),
('1','10','Alajuelita'),
('1','11','Vásquez de Coronado'),
('1','12','Acosta'),
('1','13','Tibás'),
('1','14','Moravia'),
('1','15','Montes de Oca'),
('1','16','Turrubares'),
('1','17','Dota'),
('1','18','Curridabat'),
('1','19','Pérez Zeledón'),
('1','20','León Cortés');

-- Provincia 2: Alajuela
INSERT INTO `sis_canton` VALUES
('2','01','Alajuela'),
('2','02','San Ramón'),
('2','03','Grecia'),
('2','04','San Mateo'),
('2','05','Atenas'),
('2','06','Naranjo'),
('2','07','Palmares'),
('2','08','Poás'),
('2','09','Orotina'),
('2','10','San Carlos'),
('2','11','Zarcero'),
('2','12','Valverde Vega'),
('2','13','Upala'),
('2','14','Los Chiles'),
('2','15','Guatuso'),
('2','16','Río Cuarto');

-- Provincia 3: Cartago
INSERT INTO `sis_canton` VALUES
('3','01','Cartago'),
('3','02','Paraíso'),
('3','03','La Unión'),
('3','04','Jiménez'),
('3','05','Turrialba'),
('3','06','Alvarado'),
('3','07','Oreamuno'),
('3','08','El Guarco');

-- Provincia 4: Heredia
INSERT INTO `sis_canton` VALUES
('4','01','Heredia'),
('4','02','Barva'),
('4','03','Santo Domingo'),
('4','04','Santa Bárbara'),
('4','05','San Rafael'),
('4','06','San Isidro'),
('4','07','Belén'),
('4','08','Flores'),
('4','09','San Pablo'),
('4','10','Sarapiquí');

-- Provincia 5: Guanacaste
INSERT INTO `sis_canton` VALUES
('5','01','Liberia'),
('5','02','Nicoya'),
('5','03','Santa Cruz'),
('5','04','Bagaces'),
('5','05','Carrillo'),
('5','06','Cañas'),
('5','07','Abangares'),
('5','08','Tilarán'),
('5','09','Nandayure'),
('5','10','La Cruz'),
('5','11','Hojancha');

-- Provincia 6: Puntarenas
INSERT INTO `sis_canton` VALUES
('6','01','Puntarenas'),
('6','02','Esparza'),
('6','03','Buenos Aires'),
('6','04','Montes de Oro'),
('6','05','Osa'),
('6','06','Quepos'),
('6','07','Golfito'),
('6','08','Coto Brus'),
('6','09','Parrita'),
('6','10','Corredores'),
('6','11','Garabito');

-- Provincia 7: Limón
INSERT INTO `sis_canton` VALUES
('7','01','Limón'),
('7','02','Pococí'),
('7','03','Siquirres'),
('7','04','Talamanca'),
('7','05','Matina'),
('7','06','Guácimo');

-- ----------------------------
-- Table structure for `sis_distrito`
-- ----------------------------
DROP TABLE IF EXISTS `sis_distrito`;
CREATE TABLE `sis_distrito` (
  `id_prov` varchar(1) NOT NULL DEFAULT '',
  `id_cant` varchar(2) NOT NULL DEFAULT '',
  `id_dist` varchar(2) NOT NULL DEFAULT '',
  `desc_dist` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id_prov`,`id_cant`,`id_dist`),
  KEY `fk_id_cant` (`id_prov`,`id_cant`),
  CONSTRAINT `fk_id_cant` FOREIGN KEY (`id_prov`, `id_cant`) REFERENCES `sis_canton` (`id_prov`, `id_cant`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of sis_distrito
-- ----------------------------
INSERT INTO `sis_distrito` VALUES
('1','01','01','Carmen'),
('1','01','02','Merced'),
('1','01','03','Hospital'),
('1','01','04','Catedral'),
('1','01','05','Zapote'),
('1','01','06','San Francisco de Dos Ríos'),
('1','01','07','Uruca'),
('1','01','08','Mata Redonda'),
('1','01','09','Pavas'),
('1','01','10','La Uruca'),
('1','01','11','Los Yoses'),
('1','01','12','San Sebastián'),
('1','01','13','San Pedro'),
('1','01','14','Santiago'),
('1','01','15','San Juan'),
('1','01','16','San Vicente'),
('1','01','17','San Isidro'),
('1','01','18','San Miguel'),
('1','01','19','San José'),
('1','01','20','San Rafael');

-- ----------------------------
-- Table structure for `sis_log`
-- ----------------------------
DROP TABLE IF EXISTS `sis_log`;
CREATE TABLE `sis_log` (
  `id_bi` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identificador para bitacora',
  `id_user` varchar(20) DEFAULT NULL,
  `date_bi` datetime DEFAULT NULL,
  `detail` blob,
  PRIMARY KEY (`id_bi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of sis_log
-- ----------------------------

-- ----------------------------
-- Table structure for `sis_login`
-- ----------------------------
DROP TABLE IF EXISTS `sis_login`;
CREATE TABLE `sis_login` (
  `id` varchar(50) NOT NULL,
  `pass` varchar(50) NOT NULL,
  `id_roll` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_roll_user` (`id_roll`),
  CONSTRAINT `fk_roll_user` FOREIGN KEY (`id_roll`) REFERENCES `sis_rolls` (`id_roll`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of sis_login
-- ----------------------------
INSERT INTO `sis_login` VALUES ('112170040', 'd41d8cd98f00b204e9800998ecf8427e', '1');
INSERT INTO `sis_login` VALUES ('rrodrigo123', '5d7845ac6ee7cfffafc5fe5f35cf666d', '2');

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
INSERT INTO `sis_user` VALUES ('112170040', 'AARON CASTILLO ALPIZAR', 'acastil@una.cr', '83419199', 'M');
INSERT INTO `sis_user` VALUES ('rrodrigo123', 'Rodrigo', 'rodrigo@una.cr', '88888888', 'M');

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
INSERT INTO `sis_rolls` VALUES ('2', 'CTFG/Subdireccion', 'Comisión de trabajos finales de graduación tiene permisos de lectura sobre los documentos de los estudiantes y puede aprobar o rechazar los trabajos finales de graduación');
INSERT INTO `sis_rolls` VALUES ('3', 'Estudiante', 'Estudiantes de la universidad tiene permisos de lectura y escritura sobre sus documentos y puede enviar solicitudes de trabajos finales de graduación');
INSERT INTO `sis_rolls` VALUES ('4', 'Asesor externo', 'Tiene acceso de lectura a los modulos del estudiante');

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

-- Permisos para CTFG/Subdirección
-- Permisos limitados para el rol CTFG/Subdirección (id_roll = 2)
INSERT INTO `sis_permits` (`id_mod`, `id_action`, `id_roll`) VALUES
(1, 1, 2), (1, 3, 2),
(2, 1, 2), (2, 2, 2), (2, 4, 2), (2, 5, 2), (2, 6, 2),
(3, 1, 2), (3, 2, 2), (3, 3, 2), (3, 4, 2), (3, 5, 2), (3, 6, 2),
(4, 1, 2), (4, 2, 2), (4, 3, 2), (4, 4, 2), (4, 5, 2), (4, 6, 2),
(5, 1, 2), (5, 2, 2), (5, 3, 2), (5, 4, 2), (5, 5, 2), (5, 6, 2),
(6, 1, 2), (6, 2, 2), (6, 3, 2), (6, 4, 2), (6, 5, 2), (6, 6, 2),
(7, 1, 2), (7, 2, 2), (7, 6, 2),
(8, 1, 2), (8, 2, 2), (8, 3, 2), (8, 4, 2), (8, 5, 2), (8, 6, 2);

-- Permisos para Estudiante
-- Permisos limitados para el rol Estudiante (id_roll = 3)
INSERT INTO `sis_permits` (`id_mod`, `id_action`, `id_roll`) VALUES
(1, 1, 3),
(3, 1, 3), (3, 2, 3), (3, 6, 3),
(4, 1, 3), (4, 2, 3), (4, 6, 3),
(5, 1, 3), (5, 2, 3), (5, 3, 3), (5, 4, 3), (5, 5, 3), (5, 6, 3),
(6, 1, 3), (6, 3, 3), (6, 4, 3), (6, 6, 3),
(7, 1, 3), (7, 6, 3),
(8, 1, 3), (8, 3, 3), (8, 4, 3);

-- Permisos para Asesor externo
-- Permisos limitados para el rol Asesor externo (id_roll = 4)
INSERT INTO `sis_permits` (`id_mod`, `id_action`, `id_roll`) VALUES
(1, 1, 4),
(2, 1, 4),
(3, 1, 4), (3, 2, 4),
(4, 1, 4), (4, 2, 4), (4, 6, 4),
(5, 1, 4),
(6, 1, 4), (6, 3, 4), (6, 4, 4), (6, 6, 4),
(7, 1, 4), (7, 6, 4),
(8, 1, 4);

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
-- Records of sis_sessions_vars
-- ----------------------------
INSERT INTO `sis_sessions_vars` VALUES ('ðœ¨ÇªZ³VB!üÆ8þ®¨', 'Ï\rµ3\"¥¿¶AfÅˆ\në €…öÈ`zñ,)T6', '1zzz333888lll111ttiiiimmmnnnfffp');
INSERT INTO `sis_sessions_vars` VALUES ('…÷&þ¡½Ú™a w‡', 'qþjæÇèÐSß}ßgÇ§M™4*d/Ö„t¨|…ö¨òP', '1zzz333888lll111ttiiiimmmnnnfffp');
INSERT INTO `sis_sessions_vars` VALUES ('[ä ó¾éF¼¥[ò“M	-Œ', 'æ‘ÈB{¯8>ç¤£¿©äÂ', '1zzz333888lll111ttiiiimmmnnnfffp');
INSERT INTO `sis_sessions_vars` VALUES ('äúfæ\Z\0G´üùUB', '3N¿>S–Ï›¬Ö\Zù!šrê:-\'}[ûàïûÉõzw*', '1zzz333888lll111ttiiiimmmnnnfffp');
INSERT INTO `sis_sessions_vars` VALUES ('^Á3\0è×fø\0[.UN', '19Ö¬àø>%„%ÃzÃåüÎ', '1zzz333888lll111ttiiiimmmnnnfffp');
INSERT INTO `sis_sessions_vars` VALUES ('ˆe`òuéUfõêcrOú', '‹ ›.¦v§Q\n:Ø	Ê', '1zzz333888lll111ttiiiimmmnnnfffp');
INSERT INTO `sis_sessions_vars` VALUES ('ïíÓê1%ådþ½ù;¦', 'Q~‹((‘w’úyeÁ‘i»', '1zzz333888lll111ttiiiimmmnnnfffp');
INSERT INTO `sis_sessions_vars` VALUES ('#]N„S‰Ó\\	Ð» ×', '\rîM÷„ÅÒØpáªKÀåâÀÁíNb,0`R¶`òiÙ&4JC&Õ«Áxº,S5Ë›uˆi\n„HšNµˆMÍÙK‡*¶ŒFúïîâÿÍ’', '1zzz333888lll111ttiiiimmmnnnfffp');
INSERT INTO `sis_sessions_vars` VALUES ('ó}ß}ª“åHngåål', '¯\rÖþŽf¹¸¼4Ò¡Îs\"ÏwÚeµiÖ@0ú<õ°&<´zWì\'¤‚©\rxgÜ?MÓû…êœD„o3aÖÓð§–4.×NbSpÊû‰HfLËï}çLH?FAñ‹ëÞÇêïÅÚ@b\nëO–ˆzXN•—>nÀ\Zôòö„€TÌˆ)Ö¾¨áMlÖ;2mÍŠåóá60&»ï\'uŒò/Ìî¥]jRÓ„fñŠ|-·É0gòÔR¿Ô4ÀÅPçØh\rÁ‹¥‡³öL¿ ÕY–¡1N˜v­oŠóPÒ³8R_Œõ&3—qEÌË-¹5¸|˜-÷‰Ü?Iî °øXô™œ]kf ”nç›lp=UcºA,þÂ,ñíô‡©â^ie·²cõ2¼eú²ë!{è½ŒR”ô&úð-Å<¾ iŽö#d#âp¿„û(­J›­0úíªm-Lnðÿù^sŽrí¸ª\0Cë¤;s£öb]å¨ðÿ=/Ýuvƒ5¡µ¥){ç›d¼Q}OaÜAA³ï;úË-rÃ‘»aÎ_`Öù‹(ìMß<üU3«3yL~½¡ù&iûÐ…»RŠ™Üé-“ã;…úÿ}Ap…7Æ†·ÒIcwLç€47ÖS-¾€Š\Zº¨]Ó@\naù±éOlbzgÛæj³9KŸæ¦d¸6Là:ÛfgÁÉ1ë	å(×#+ÞúÁ11ö„€-\'ç˜÷.LãE\Z+	‹B„w!Ð0S9«*Îa™&ûC­x\"ªØ©P39=ŸaPæäŒæ~aª*Ÿa6—b½×k†;Ùi+ö\"0sõh?ÑáõÚJèˆ)óÓZ€ì[Øl¢ü–ÖtÅÆB™¯âŠmër¾°.‹:‰Hœêwü±øL«.(¾‘L”é\r ¥aà½¦Å´Ï+’-S’^Uot&.ä`p_+ÕVü©‰=7ÒË¬óH\rp1:„ þÌ<Úøc¶ªáðõSµ\ZÂÌÕ;FfÛ ­’4{?ÝÕÐÒ§weVò{4u¾Û£¼øz3ÃÞ„Žæƒ\\çdÛE^+ï¸{ýOí±<vmöÄÃ)’¢ó|pý´û…2gSV‹´5~%æM— šÃoò´éãeTr€Ï£ôÜ¯ª´†</Ð!\rãÊ€~‡õì”µÍ†’IdæT‹Š«ãUW	}_³9ðOèYªÜhÎŽ\nãi7ù<y\nùŽ\nØ±êùŽ¶³†úÍ`gi¶ƒû‹u‹[‘©º‹2X³<›¨¢Îa³OÞäOPÏSÐžû%™þfh…Œ	T0ÍÎêh«È—Ò˜ÌªpÄ£&9Rè\'g96; Œ{„‰/\0ÀHàáóÙ·ÄòTq\Z9Ñú7¡•—ùš5ìL{BÛ­6e:i¬ÿ5Œ/H3-]s¥vì/’\"êùˆå“cÁ7ô~Š¯Q$ËFÓpcköœ @F•à:LŒÎi0¦/‚0ä´ûz´}Û­ë5Å«3fÏ·«ù¬2: 4¦³õ³Kñ§­Ç¿Ò¾Ù‚Æ\\ÞÆ¾ò»:‹?X°¯ÈÒælÇl\Zîçþ\rl>#ûfŒ©Ï)Ÿ$pY:ó˜kLgQø1’ùÏ&ø\Z+Id‹[OE¢;}\'³¹Òì÷u\\¼ñÉhÇË4rý´3\0’„&“u©rhEÉœß#éE©zªFÂ6ÑŸŠ.b>@Ã¦åò`úå¦¼™\0£Õ½\Zw \\ª¸ÈQ{oCÉndvV¹¤ùhûu*Pâ¥uoWNè¦J‘àŠÌ\\™IL¼ÒM.A¤Xí¼ò…+zõ‰ŸÒp/}G2;¾\Z§^j/fEá?`	ùo:MÃ	›‹§tQ\'&K>#Ô”zPÎ˜½Ð\0\"ú\\u·þwÑæ..Œ]u u††©j=-tÖ\"u‚y\Z/ƒ‘—Pc‡»ÜÍ(‡eÓµ¾+xüañÐ|eLMw\'©µËçÏÉŸ4yV±/WïÙ-¡è\ZE“b#t½uUtóed]z|œXmwrŸª…ÁÉ|¡qòYÈZñ¯¾òE¦GäÁã„GÚyNŽb¹Íš&oÒ¯³±ÊÚmÈ UH|4ðŽ¢ôšlIQV-<B7À¸B\rÇÍÿPVE\'ÁsCæZØŽ\"ƒ’\rêØ^”i\rˆü¸±¤ÍöpÊX†fºÑ$Z2|•	‰~]Yûí,ó+äpúIà31´øŒ2Õmxq5“7²Gú­e`ÀxÛßÂå/Íä-O,êÐ\r>DÁ–Éùš4T´)_`÷ÿ½?62C«†{£°‰€œ6ªnðgÈ¥=µÖ:$×Ô/ó¯§»šj0arøÂõM\"|cÒVlBºœ\\Ì\0RÈé«ErF\rrÀ³üz”ýÄÙBQŸªC3…I’8è¾üÀÜŠŠÍÔÐêì×Ç5fÜª\rf¼P…»mC%eUÐŸŒÍø:…Ž/$ŒntÉt!gu¾257éÂ}@÷Ãƒ€\ZŠ\nþ31‚ò\n$e…†UmrÛ°þ>®ÓµIï_ q€Ë¿¸’¦Wwîü¶Ü«Óð ƒA‚@ Ú%V²æW­:Ø\'i‹øËù¸ÔmÖ!«£UÑi”Lø†6G\Z,èlƒÁø#µž×¦Ã–ï·\'Ð7cýà@÷’ü‡®{ÄÕù²Íuëo*óQÖÐ$ôÕ•àÌŽ¬`,”rp)G]EÈPýþu`ÇøCC5á©ý6•\nc•ÏUñ\"Žh7¨Qž°×Ä²ÖI›HªRWeÇ[ÿä/µè5‚ ôòÜ¸$Sôgi¯éLìÂ2Ty¸WAXî¹u\rö0´ï‘”áÛÈ\0p°=Mˆä\"-’ù¿¨\Z— ÏFÞ’\rÌ1krõŸ•ÿ|Ø,e­\\X­¡fèñð‡‡G÷ ã’ÃßÔ,!úL»q‰ªÖ“ibÂZëŸhÜÙæ×Tx-¤{GVtÈ~°få°À=O²ÿFM;GRì®6ž+Â™1wmTÍ¶±3«=R¿IaŽ>ÿFÚ–ª5rß³¨è•%o }â]ÌYÆSWìx*KW ¦ìßLa¶ z¶å!ºŠ2D²±¡…Y†p¶·U‡•;²¯#ã”Óx\rýÐZ)÷ñ^§ITO¼_A/Ý\ZýØœU­l#ÔßòËœ`x°ü6?odp\\dõJÓI´Ò— IôŠ¶4J;Íz||›$oº:pVi³\"é{¾q‰áÚq–ËMoÆ“¡ê¨tÊãrÈbd®ð?Óp\r<#|sVß0í4%úf·ëXšçßAŒTññÚŸì)þeô\"„Ù\ZÁ|ª‡]rd<hŸNÄD5çZ²ÑÃa%a²ÙlþÊ—[pÓ\'I‹B¶X‰„ûf¬~l6Óí‚G ðèV™-#ÜA,–×l±’¾Ðô¸\\â0»Uõ1%ólÆû·x\Z®èÎŒZÕ¥fÓ§Äim«Ð$Rùï¼`e[ë—fMímU‘#GýöEªw”K\"\nöŸÕ$2ìÊåÉýë—’¿oñ‘Qi‘¾+ÀÙç—sZ#£¶Œà7 s‰ðèë‰óy_7™ÐDÈÁ|\\÷â³»$êê@ÜÐw˜•OÆ¶Å›æh¥j»â²4¸³À;Õò;9$\r#œxÄ˜Aò•úªkâ2o~½Ôšî¤Ðµ+îá•–nz‡/þÝ³]\rïKŠÝ/\r•t!füuÒÔÿí¦èÓUD>¹9’,k}-ã>Ž0š¿Hgçˆ™nÉòÿòë1.ÕtðXggÁ‹ >7¿Ö/mF›B£¹³ywúÑŒa°‰ØI€‰OAöÚ~~¦éH7¾’¹Yã|•ëËwßC‡P~™&þbü\Z\\ ÃˆPÌaQÆINÅÎ-*b-ß=ß¢FÐ°;!ÑÌäŸÈ…€6‰\ZÖ‰?«ëcG“ÿÞ~t˜l;Ëg%¯H7ˆî”ÿu–Ã^ñ–´B\r5PsKŸS¡\nÍ’VWpŸðoIbjèòå1­ëö‹Æí?VoI\0E‘]—[vÃu°ó¯u¾õmÎfå!|¤žä\\lRÏGO‚K1½¦)~ýÌ\rK$•ÜòÄÅÕÏ–¦7oÒ	A°DZ	2KÌSx\"¬¬kto2{„3üÌ.‰|/úðæÅf9‚ç±þ‹¨†þ­§éºÜn9ò‹¬eÄ=4Ì-†µ­‡PLÅj^¹ž«', '1zzz333888lll111ttiiiimmmnnnfffp');
INSERT INTO `sis_sessions_vars` VALUES ('ðœ¨ÇªZ³VB!üÆ8þ®¨', 'ß!hÑŽÊ³AÓiÛKtÇ©i§E\0~¬© \\ñžBr=ûÛ>Ám¥\Z¤½:S\"5GZg¡cÏ\rÇçEqŠ{ãþ‘€d!@(q­Ë[=1Ä¥;¬', '0k33888pppuuugg000xxxhhhrroooaaa');
INSERT INTO `sis_sessions_vars` VALUES ('…÷&þ¡½Ú™a w‡', '·-dÔ±Lÿ-Ïvm§qjÀž', '0k33888pppuuugg000xxxhhhrroooaaa');
INSERT INTO `sis_sessions_vars` VALUES ('[ä ó¾éF¼¥[ò“M	-Œ', 'y\"Ãºlƒ,„ù®w5\r+d', '0k33888pppuuugg000xxxhhhrroooaaa');
INSERT INTO `sis_sessions_vars` VALUES ('äúfæ\Z\0G´üùUB', '3N¿>S–Ï›¬Ö\Zù!šrê:-\'}[ûàïûÉõzw*', '0k33888pppuuugg000xxxhhhrroooaaa');
INSERT INTO `sis_sessions_vars` VALUES ('^Á3\0è×fø\0[.UN', '19Ö¬àø>%„%ÃzÃåüÎ', '0k33888pppuuugg000xxxhhhrroooaaa');
INSERT INTO `sis_sessions_vars` VALUES ('ˆe`òuéUfõêcrOú', '‹ ›.¦v§Q\n:Ø	Ê', '0k33888pppuuugg000xxxhhhrroooaaa');
INSERT INTO `sis_sessions_vars` VALUES ('ïíÓê1%ådþ½ù;¦', 'Q~‹((‘w’úyeÁ‘i»', '0k33888pppuuugg000xxxhhhrroooaaa');
INSERT INTO `sis_sessions_vars` VALUES ('#]N„S‰Ó\\	Ð» ×', '\rîM÷„ÅÒØpáªKÀåâÀÁíNb,0`R¶`òiÙ&4JC&Õ«Áxº,S5Ë›uˆi\n„HšNµˆMÍÙK‡*¶ŒFúïîâÿÍ’', '0k33888pppuuugg000xxxhhhrroooaaa');
INSERT INTO `sis_sessions_vars` VALUES ('ó}ß}ª“åHngåål', '¯\rÖþŽf¹¸¼4Ò¡Îs\"ÏwÚeµiÖ@0ú<õ°&<´zWì\'¤‚©\rxgÜ?MÓû…êœD„o3aÖÓð§–4.×NbSpÊû‰HfLËï}çLH?FAñ‹ëÞÇêïÅÚ@b\nëO–ˆzXN•—>nÀ\Zôòö„€TÌˆ)Ö¾¨áMlÖ;2mÍŠåóá60&»ï\'uŒò/Ìî¥]jRÓ„fñŠ|-·É0gòÔR¿Ô4ÀÅPçØh\rÁ‹¥‡³öL¿ ÕY–¡1N˜v­oŠóPÒ³8R_Œõ&3—qEÌË-¹5¸|˜-÷‰Ü?Iî °øXô™œ]kf ”nç›lp=UcºA,þÂ,ñíô‡©â^ie·²cõ2¼eú²ë!{è½ŒR”ô&úð-Å<¾ iŽö#d#âp¿„û(­J›­0úíªm-Lnðÿù^sŽrí¸ª\0Cë¤;s£öb]å¨ðÿ=/Ýuvƒ5¡µ¥){ç›d¼Q}OaÜAA³ï;úË-rÃ‘»aÎ_`Öù‹(ìMß<üU3«3yL~½¡ù&iûÐ…»RŠ™Üé-“ã;…úÿ}Ap…7Æ†·ÒIcwLç€47ÖS-¾€Š\Zº¨]Ó@\naù±éOlbzgÛæj³9KŸæ¦d¸6Là:ÛfgÁÉ1ë	å(×#+ÞúÁ11ö„€-\'ç˜÷.LãE\Z+	‹B„w!Ð0S9«*Îa™&ûC­x\"ªØ©P39=ŸaPæäŒæ~aª*Ÿa6—b½×k†;Ùi+ö\"0sõh?ÑáõÚJèˆ)óÓZ€ì[Øl¢ü–ÖtÅÆB™¯âŠmër¾°.‹:‰Hœêwü±øL«.(¾‘L”é\r ¥aà½¦Å´Ï+’-S’^Uot&.ä`p_+ÕVü©‰=7ÒË¬óH\rp1:„ þÌ<Úøc¶ªáðõSµ\ZÂÌÕ;FfÛ ­’4{?ÝÕÐÒ§weVò{4u¾Û£¼øz3ÃÞ„Žæƒ\\çdÛE^+ï¸{ýOí±<vmöÄÃ)’¢ó|pý´û…2gSV‹´5~%æM— šÃoò´éãeTr€Ï£ôÜ¯ª´†</Ð!\rãÊ€~‡õì”µÍ†’IdæT‹Š«ãUW	}_³9ðOèYªÜhÎŽ\nãi7ù<y\nùŽ\nØ±êùŽ¶³†úÍ`gi¶ƒû‹u‹[‘©º‹2X³<›¨¢Îa³OÞäOPÏSÐžû%™þfh…Œ	T0ÍÎêh«È—Ò˜ÌªpÄ£&9Rè\'g96; Œ{„‰/\0ÀHàáóÙ·ÄòTq\Z9Ñú7¡•—ùš5ìL{BÛ­6e:i¬ÿ5Œ/H3-]s¥vì/’\"êùˆå“cÁ7ô~Š¯Q$ËFÓpcköœ @F•à:LŒÎi0¦/‚0ä´ûz´}Û­ë5Å«3fÏ·«ù¬2: 4¦³õ³Kñ§­Ç¿Ò¾Ù‚Æ\\ÞÆ¾ò»:‹?X°¯ÈÒælÇl\Zîçþ\rl>#ûfŒ©Ï)Ÿ$pY:ó˜kLgQø1’ùÏ&ø\Z+Id‹[OE¢;}\'³¹Òì÷u\\¼ñÉhÇË4rý´3\0’„&“u©rhEÉœß#éE©zªFÂ6ÑŸŠ.b>@Ã¦åò`úå¦¼™\0£Õ½\Zw \\ª¸ÈQ{oCÉndvV¹¤ùhûu*Pâ¥uoWNè¦J‘àŠÌ\\™IL¼ÒM.A¤Xí¼ò…+zõ‰ŸÒp/}G2;¾\Z§^j/fEá?`	ùo:MÃ	›‹§tQ\'&K>#Ô”zPÎ˜½Ð\0\"ú\\u·þwÑæ..Œ]u u††©j=-tÖ\"u‚y\Z/ƒ‘—Pc‡»ÜÍ(‡eÓµ¾+xüañÐ|eLMw\'©µËçÏÉŸ4yV±/WïÙ-¡è\ZE“b#t½uUtóed]z|œXmwrŸª…ÁÉ|¡qòYÈZñ¯¾òE¦GäÁã„GÚyNŽb¹Íš&oÒ¯³±ÊÚmÈ UH|4ðŽ¢ôšlIQV-<B7À¸B\rÇÍÿPVE\'ÁsCæZØŽ\"ƒ’\rêØ^”i\rˆü¸±¤ÍöpÊX†fºÑ$Z2|•	‰~]Yûí,ó+äpúIà31´øŒ2Õmxq5“7²Gú­e`ÀxÛßÂå/Íä-O,êÐ\r>DÁ–Éùš4T´)_`÷ÿ½?62C«†{£°‰€œ6ªnðgÈ¥=µÖ:$×Ô/ó¯§»šj0arøÂõM\"|cÒVlBºœ\\Ì\0RÈé«ErF\rrÀ³üz”ýÄÙBQŸªC3…I’8è¾üÀÜŠŠÍÔÐêì×Ç5fÜª\rf¼P…»mC%eUÐŸŒÍø:…Ž/$ŒntÉt!gu¾257éÂ}@÷Ãƒ€\ZŠ\nþ31‚ò\n$e…†UmrÛ°þ>®ÓµIï_ q€Ë¿¸’¦Wwîü¶Ü«Óð ƒA‚@ Ú%V²æW­:Ø\'i‹øËù¸ÔmÖ!«£UÑi”Lø†6G\Z,èlƒÁø#µž×¦Ã–ï·\'Ð7cýà@÷’ü‡®{ÄÕù²Íuëo*óQÖÐ$ôÕ•àÌŽ¬`,”rp)G]EÈPýþu`ÇøCC5á©ý6•\nc•ÏUñ\"Žh7¨Qž°×Ä²ÖI›HªRWeÇ[ÿä/µè5‚ ôòÜ¸$Sôgi¯éLìÂ2Ty¸WAXî¹u\rö0´ï‘”áÛÈ\0p°=Mˆä\"-’ù¿¨\Z— ÏFÞ’\rÌ1krõŸ•ÿ|Ø,e­\\X­¡fèñð‡‡G÷ ã’ÃßÔ,!úL»q‰ªÖ“ibÂZëŸhÜÙæ×Tx-¤{GVtÈ~°få°À=O²ÿFM;GRì®6ž+Â™1wmTÍ¶±3«=R¿IaŽ>ÿFÚ–ª5rß³¨è•%o }â]ÌYÆSWìx*KW ¦ìßLa¶ z¶å!ºŠ2D²±¡…Y†p¶·U‡•;²¯#ã”Óx\rýÐZ)÷ñ^§ITO¼_A/Ý\ZýØœU­l#ÔßòËœ`x°ü6?odp\\dõJÓI´Ò— IôŠ¶4J;Íz||›$oº:pVi³\"é{¾q‰áÚq–ËMoÆ“¡ê¨tÊãrÈbd®ð?Óp\r<#|sVß0í4%úf·ëXšçßAŒTññÚŸì)þeô\"„Ù\ZÁ|ª‡]rd<hŸNÄD5çZ²ÑÃa%a²ÙlþÊ—[pÓ\'I‹B¶X‰„ûf¬~l6Óí‚G ðèV™-#ÜA,–×l±’¾Ðô¸\\â0»Uõ1%ólÆû·x\Z®èÎŒZÕ¥fÓ§Äim«Ð$Rùï¼`e[ë—fMímU‘#GýöEªw”K\"\nöŸÕ$2ìÊåÉýë—’¿oñ‘Qi‘¾+ÀÙç—sZ#£¶Œà7 s‰ðèë‰óy_7™ÐDÈÁ|\\÷â³»$êê@ÜÐw˜•OÆ¶Å›æh¥j»â²4¸³À;Õò;9$\r#œxÄ˜Aò•úªkâ2o~½Ôšî¤Ðµ+îá•–nz‡/þÝ³]\rïKŠÝ/\r•t!füuÒÔÿí¦èÓUD>¹9’,k}-ã>Ž0š¿Hgçˆ™nÉòÿòë1.ÕtðXggÁ‹ >7¿Ö/mF›B£¹³ywúÑŒa°‰ØI€‰OAöÚ~~¦éH7¾’¹Yã|•ëËwßC‡P~™&þbü\Z\\ ÃˆPÌaQÆINÅÎ-*b-ß=ß¢FÐ°;!ÑÌäŸÈ…€6‰\ZÖ‰?«ëcG“ÿÞ~t˜l;Ëg%¯H7ˆî”ÿu–Ã^ñ–´B\r5PsKŸS¡\nÍ’VWpŸðoIbjèòå1­ëö‹Æí?VoI\0E‘]—[vÃu°ó¯u¾õmÎfå!|¤žä\\lRÏGO‚K1½¦)~ýÌ\rK$•ÜòÄÅÕÏ–¦7oÒ	A°DZ	2KÌSx\"¬¬kto2{„3üÌ.‰|/úðæÅf9‚ç±þ‹¨†þ­§éºÜn9ò‹¬eÄ=4Ì-†µ­‡PLÅj^¹ž«', '0k33888pppuuugg000xxxhhhrroooaaa');
INSERT INTO `sis_sessions_vars` VALUES ('ðœ¨ÇªZ³VB!üÆ8þ®¨', 'öâeíÊtÕÚjxú_ì3ã%«·¦4](pï2“ªÊß}úi§E\0~¬© \\ñžBrã¤ÒÚ?DfÎƒ\Z¦j½°FDx¿~gˆãèÉDeN', 'qccxxxnnfffwwwqqggg000kkhhh77ooo');
INSERT INTO `sis_sessions_vars` VALUES ('…÷&þ¡½Ú™a w‡', '·-dÔ±Lÿ-Ïvm§qjÀž', 'qccxxxnnfffwwwqqggg000kkhhh77ooo');
INSERT INTO `sis_sessions_vars` VALUES ('[ä ó¾éF¼¥[ò“M	-Œ', 'y\"Ãºlƒ,„ù®w5\r+d', 'qccxxxnnfffwwwqqggg000kkhhh77ooo');
INSERT INTO `sis_sessions_vars` VALUES ('äúfæ\Z\0G´üùUB', '3N¿>S–Ï›¬Ö\Zù!šrê:-\'}[ûàïûÉõzw*', 'qccxxxnnfffwwwqqggg000kkhhh77ooo');
INSERT INTO `sis_sessions_vars` VALUES ('^Á3\0è×fø\0[.UN', '19Ö¬àø>%„%ÃzÃåüÎ', 'qccxxxnnfffwwwqqggg000kkhhh77ooo');
INSERT INTO `sis_sessions_vars` VALUES ('ˆe`òuéUfõêcrOú', '‹ ›.¦v§Q\n:Ø	Ê', 'qccxxxnnfffwwwqqggg000kkhhh77ooo');
INSERT INTO `sis_sessions_vars` VALUES ('ïíÓê1%ådþ½ù;¦', 'Q~‹((‘w’úyeÁ‘i»', 'qccxxxnnfffwwwqqggg000kkhhh77ooo');
INSERT INTO `sis_sessions_vars` VALUES ('#]N„S‰Ó\\	Ð» ×', '\rîM÷„ÅÒØpáªKÀåâÀÁíNb,0`R¶`òiÙ&4JC&Õ«Áxº,S5Ë›uˆi\n„HšNµˆMÍÙK‡*¶ŒFúïîâÿÍ’', 'qccxxxnnfffwwwqqggg000kkhhh77ooo');
INSERT INTO `sis_sessions_vars` VALUES ('ó}ß}ª“åHngåål', '¯\rÖþŽf¹¸¼4Ò¡Îs\"ÏwÚeµiÖ@0ú<õ°&<´zWì\'¤‚©\rxgÜ?MÓû…êœD„o3aÖÓð§–4.×NbSpÊû‰HfLËï}çLH?FAñ‹ëÞÇêïÅÚ@b\nëO–ˆzXN•—>nÀ\Zôòö„€TÌˆ)Ö¾¨áMlÖ;2mÍŠåóá60&»ï\'uŒò/Ìî¥]jRÓ„fñŠ|-·É0gòÔR¿Ô4ÀÅPçØh\rÁ‹¥‡³öL¿ ÕY–¡1N˜v­oŠóPÒ³8R_Œõ&3—qEÌË-¹5¸|˜-÷‰Ü?Iî °øXô™œ]kf ”nç›lp=UcºA,þÂ,ñíô‡©â^ie·²cõ2¼eú²ë!{è½ŒR”ô&úð-Å<¾ iŽö#d#âp¿„û(­J›­0úíªm-Lnðÿù^sŽrí¸ª\0Cë¤;s£öb]å¨ðÿ=/Ýuvƒ5¡µ¥){ç›d¼Q}OaÜAA³ï;úË-rÃ‘»aÎ_`Öù‹(ìMß<üU3«3yL~½¡ù&iûÐ…»RŠ™Üé-“ã;…úÿ}Ap…7Æ†·ÒIcwLç€47ÖS-¾€Š\Zº¨]Ó@\naù±éOlbzgÛæj³9KŸæ¦d¸6Là:ÛfgÁÉ1ë	å(×#+ÞúÁ11ö„€-\'ç˜÷.LãE\Z+	‹B„w!Ð0S9«*Îa™&ûC­x\"ªØ©P39=ŸaPæäŒæ~aª*Ÿa6—b½×k†;Ùi+ö\"0sõh?ÑáõÚJèˆ)óÓZ€ì[Øl¢ü–ÖtÅÆB™¯âŠmër¾°.‹:‰Hœêwü±øL«.(¾‘L”é\r ¥aà½¦Å´Ï+’-S’^Uot&.ä`p_+ÕVü©‰=7ÒË¬óH\rp1:„ þÌ<Úøc¶ªáðõSµ\ZÂÌÕ;FfÛ ­’4{?ÝÕÐÒ§weVò{4u¾Û£¼øz3ÃÞ„Žæƒ\\çdÛE^+ï¸{ýOí±<vmöÄÃ)’¢ó|pý´û…2gSV‹´5~%æM— šÃoò´éãeTr€Ï£ôÜ¯ª´†</Ð!\rãÊ€~‡õì”µÍ†’IdæT‹Š«ãUW	}_³9ðOèYªÜhÎŽ\nãi7ù<y\nùŽ\nØ±êùŽ¶³†úÍ`gi¶ƒû‹u‹[‘©º‹2X³<›¨¢Îa³OÞäOPÏSÐžû%™þfh…Œ	T0ÍÎêh«È—Ò˜ÌªpÄ£&9Rè\'g96; Œ{„‰/\0ÀHàáóÙ·ÄòTq\Z9Ñú7¡•—ùš5ìL{BÛ­6e:i¬ÿ5Œ/H3-]s¥vì/’\"êùˆå“cÁ7ô~Š¯Q$ËFÓpcköœ @F•à:LŒÎi0¦/‚0ä´ûz´}Û­ë5Å«3fÏ·«ù¬2: 4¦³õ³Kñ§­Ç¿Ò¾Ù‚Æ\\ÞÆ¾ò»:‹?X°¯ÈÒælÇl\Zîçþ\rl>#ûfŒ©Ï)Ÿ$pY:ó˜kLgQø1’ùÏ&ø\Z+Id‹[OE¢;}\'³¹Òì÷u\\¼ñÉhÇË4rý´3\0’„&“u©rhEÉœß#éE©zªFÂ6ÑŸŠ.b>@Ã¦åò`úå¦¼™\0£Õ½\Zw \\ª¸ÈQ{oCÉndvV¹¤ùhûu*Pâ¥uoWNè¦J‘àŠÌ\\™IL¼ÒM.A¤Xí¼ò…+zõ‰ŸÒp/}G2;¾\Z§^j/fEá?`	ùo:MÃ	›‹§tQ\'&K>#Ô”zPÎ˜½Ð\0\"ú\\u·þwÑæ..Œ]u u††©j=-tÖ\"u‚y\Z/ƒ‘—Pc‡»ÜÍ(‡eÓµ¾+xüañÐ|eLMw\'©µËçÏÉŸ4yV±/WïÙ-¡è\ZE“b#t½uUtóed]z|œXmwrŸª…ÁÉ|¡qòYÈZñ¯¾òE¦GäÁã„GÚyNŽb¹Íš&oÒ¯³±ÊÚmÈ UH|4ðŽ¢ôšlIQV-<B7À¸B\rÇÍÿPVE\'ÁsCæZØŽ\"ƒ’\rêØ^”i\rˆü¸±¤ÍöpÊX†fºÑ$Z2|•	‰~]Yûí,ó+äpúIà31´øŒ2Õmxq5“7²Gú­e`ÀxÛßÂå/Íä-O,êÐ\r>DÁ–Éùš4T´)_`÷ÿ½?62C«†{£°‰€œ6ªnðgÈ¥=µÖ:$×Ô/ó¯§»šj0arøÂõM\"|cÒVlBºœ\\Ì\0RÈé«ErF\rrÀ³üz”ýÄÙBQŸªC3…I’8è¾üÀÜŠŠÍÔÐêì×Ç5fÜª\rf¼P…»mC%eUÐŸŒÍø:…Ž/$ŒntÉt!gu¾257éÂ}@÷Ãƒ€\ZŠ\nþ31‚ò\n$e…†UmrÛ°þ>®ÓµIï_ q€Ë¿¸’¦Wwîü¶Ü«Óð ƒA‚@ Ú%V²æW­:Ø\'i‹øËù¸ÔmÖ!«£UÑi”Lø†6G\Z,èlƒÁø#µž×¦Ã–ï·\'Ð7cýà@÷’ü‡®{ÄÕù²Íuëo*óQÖÐ$ôÕ•àÌŽ¬`,”rp)G]EÈPýþu`ÇøCC5á©ý6•\nc•ÏUñ\"Žh7¨Qž°×Ä²ÖI›HªRWeÇ[ÿä/µè5‚ ôòÜ¸$Sôgi¯éLìÂ2Ty¸WAXî¹u\rö0´ï‘”áÛÈ\0p°=Mˆä\"-’ù¿¨\Z— ÏFÞ’\rÌ1krõŸ•ÿ|Ø,e­\\X­¡fèñð‡‡G÷ ã’ÃßÔ,!úL»q‰ªÖ“ibÂZëŸhÜÙæ×Tx-¤{GVtÈ~°få°À=O²ÿFM;GRì®6ž+Â™1wmTÍ¶±3«=R¿IaŽ>ÿFÚ–ª5rß³¨è•%o }â]ÌYÆSWìx*KW ¦ìßLa¶ z¶å!ºŠ2D²±¡…Y†p¶·U‡•;²¯#ã”Óx\rýÐZ)÷ñ^§ITO¼_A/Ý\ZýØœU­l#ÔßòËœ`x°ü6?odp\\dõJÓI´Ò— IôŠ¶4J;Íz||›$oº:pVi³\"é{¾q‰áÚq–ËMoÆ“¡ê¨tÊãrÈbd®ð?Óp\r<#|sVß0í4%úf·ëXšçßAŒTññÚŸì)þeô\"„Ù\ZÁ|ª‡]rd<hŸNÄD5çZ²ÑÃa%a²ÙlþÊ—[pÓ\'I‹B¶X‰„ûf¬~l6Óí‚G ðèV™-#ÜA,–×l±’¾Ðô¸\\â0»Uõ1%ólÆû·x\Z®èÎŒZÕ¥fÓ§Äim«Ð$Rùï¼`e[ë—fMímU‘#GýöEªw”K\"\nöŸÕ$2ìÊåÉýë—’¿oñ‘Qi‘¾+ÀÙç—sZ#£¶Œà7 s‰ðèë‰óy_7™ÐDÈÁ|\\÷â³»$êê@ÜÐw˜•OÆ¶Å›æh¥j»â²4¸³À;Õò;9$\r#œxÄ˜Aò•úªkâ2o~½Ôšî¤Ðµ+îá•–nz‡/þÝ³]\rïKŠÝ/\r•t!füuÒÔÿí¦èÓUD>¹9’,k}-ã>Ž0š¿Hgçˆ™nÉòÿòë1.ÕtðXggÁ‹ >7¿Ö/mF›B£¹³ywúÑŒa°‰ØI€‰OAöÚ~~¦éH7¾’¹Yã|•ëËwßC‡P~™&þbü\Z\\ ÃˆPÌaQÆINÅÎ-*b-ß=ß¢FÐ°;!ÑÌäŸÈ…€6‰\ZÖ‰?«ëcG“ÿÞ~t˜l;Ëg%¯H7ˆî”ÿu–Ã^ñ–´B\r5PsKŸS¡\nÍ’VWpŸðoIbjèòå1­ëö‹Æí?VoI\0E‘]—[vÃu°ó¯u¾õmÎfå!|¤žä\\lRÏGO‚K1½¦)~ýÌ\rK$•ÜòÄÅÕÏ–¦7oÒ	A°DZ	2KÌSx\"¬¬kto2{„3üÌ.‰|/úðæÅf9‚ç±þ‹¨†þ­§éºÜn9ò‹¬eÄ=4Ì-†µ­‡PLÅj^¹ž«', 'qccxxxnnfffwwwqqggg000kkhhh77ooo');
INSERT INTO `sis_sessions_vars` VALUES ('ðœ¨ÇªZ³VB!üÆ8þ®¨', 'ûocÂ,çÃâ™Y§ÏóÔ½i§E\0~¬© \\ñžBr=ûÛ>Ám¥\Z¤½:S\"5GZjTÙdVï \r‰.jsg8œ¯‹>Ë³)×žU²', '2eeeaabbbvvvll111777oooaaabbvvvs');
INSERT INTO `sis_sessions_vars` VALUES ('…÷&þ¡½Ú™a w‡', '·-dÔ±Lÿ-Ïvm§qjÀž', '2eeeaabbbvvvll111777oooaaabbvvvs');
INSERT INTO `sis_sessions_vars` VALUES ('[ä ó¾éF¼¥[ò“M	-Œ', 'y\"Ãºlƒ,„ù®w5\r+d', '2eeeaabbbvvvll111777oooaaabbvvvs');
INSERT INTO `sis_sessions_vars` VALUES ('äúfæ\Z\0G´üùUB', '3N¿>S–Ï›¬Ö\Zù!šrê:-\'}[ûàïûÉõzw*', '2eeeaabbbvvvll111777oooaaabbvvvs');
INSERT INTO `sis_sessions_vars` VALUES ('^Á3\0è×fø\0[.UN', '19Ö¬àø>%„%ÃzÃåüÎ', '2eeeaabbbvvvll111777oooaaabbvvvs');
INSERT INTO `sis_sessions_vars` VALUES ('ˆe`òuéUfõêcrOú', '‹ ›.¦v§Q\n:Ø	Ê', '2eeeaabbbvvvll111777oooaaabbvvvs');
INSERT INTO `sis_sessions_vars` VALUES ('ïíÓê1%ådþ½ù;¦', 'Q~‹((‘w’úyeÁ‘i»', '2eeeaabbbvvvll111777oooaaabbvvvs');
INSERT INTO `sis_sessions_vars` VALUES ('#]N„S‰Ó\\	Ð» ×', '\rîM÷„ÅÒØpáªKÀåâÀÁíNb,0`R¶`òiÙ&4JC&Õ«Áxº,S5Ë›uˆi\n„HšNµˆMÍÙK‡*¶ŒFúïîâÿÍ’', '2eeeaabbbvvvll111777oooaaabbvvvs');
INSERT INTO `sis_sessions_vars` VALUES ('ó}ß}ª“åHngåål', '¯\rÖþŽf¹¸¼4Ò¡Îs\"ÏwÚeµiÖ@0ú<õ°&<´zWì\'¤‚©\rxgÜ?MÓû…êœD„o3aÖÓð§–4.×NbSpÊû‰HfLËï}çLH?FAñ‹ëÞÇêïÅÚ@b\nëO–ˆzXN•—>nÀ\Zôòö„€TÌˆ)Ö¾¨áMlÖ;2mÍŠåóá60&»ï\'uŒò/Ìî¥]jRÓ„fñŠ|-·É0gòÔR¿Ô4ÀÅPçØh\rÁ‹¥‡³öL¿ ÕY–¡1N˜v­oŠóPÒ³8R_Œõ&3—qEÌË-¹5¸|˜-÷‰Ü?Iî °øXô™œ]kf ”nç›lp=UcºA,þÂ,ñíô‡©â^ie·²cõ2¼eú²ë!{è½ŒR”ô&úð-Å<¾ iŽö#d#âp¿„û(­J›­0úíªm-Lnðÿù^sŽrí¸ª\0Cë¤;s£öb]å¨ðÿ=/Ýuvƒ5¡µ¥){ç›d¼Q}OaÜAA³ï;úË-rÃ‘»aÎ_`Öù‹(ìMß<üU3«3yL~½¡ù&iûÐ…»RŠ™Üé-“ã;…úÿ}Ap…7Æ†·ÒIcwLç€47ÖS-¾€Š\Zº¨]Ó@\naù±éOlbzgÛæj³9KŸæ¦d¸6Là:ÛfgÁÉ1ë	å(×#+ÞúÁ11ö„€-\'ç˜÷.LãE\Z+	‹B„w!Ð0S9«*Îa™&ûC­x\"ªØ©P39=ŸaPæäŒæ~aª*Ÿa6—b½×k†;Ùi+ö\"0sõh?ÑáõÚJèˆ)óÓZ€ì[Øl¢ü–ÖtÅÆB™¯âŠmër¾°.‹:‰Hœêwü±øL«.(¾‘L”é\r ¥aà½¦Å´Ï+’-S’^Uot&.ä`p_+ÕVü©‰=7ÒË¬óH\rp1:„ þÌ<Úøc¶ªáðõSµ\ZÂÌÕ;FfÛ ­’4{?ÝÕÐÒ§weVò{4u¾Û£¼øz3ÃÞ„Žæƒ\\çdÛE^+ï¸{ýOí±<vmöÄÃ)’¢ó|pý´û…2gSV‹´5~%æM— šÃoò´éãeTr€Ï£ôÜ¯ª´†</Ð!\rãÊ€~‡õì”µÍ†’IdæT‹Š«ãUW	}_³9ðOèYªÜhÎŽ\nãi7ù<y\nùŽ\nØ±êùŽ¶³†úÍ`gi¶ƒû‹u‹[‘©º‹2X³<›¨¢Îa³OÞäOPÏSÐžû%™þfh…Œ	T0ÍÎêh«È—Ò˜ÌªpÄ£&9Rè\'g96; Œ{„‰/\0ÀHàáóÙ·ÄòTq\Z9Ñú7¡•—ùš5ìL{BÛ­6e:i¬ÿ5Œ/H3-]s¥vì/’\"êùˆå“cÁ7ô~Š¯Q$ËFÓpcköœ @F•à:LŒÎi0¦/‚0ä´ûz´}Û­ë5Å«3fÏ·«ù¬2: 4¦³õ³Kñ§­Ç¿Ò¾Ù‚Æ\\ÞÆ¾ò»:‹?X°¯ÈÒælÇl\Zîçþ\rl>#ûfŒ©Ï)Ÿ$pY:ó˜kLgQø1’ùÏ&ø\Z+Id‹[OE¢;}\'³¹Òì÷u\\¼ñÉhÇË4rý´3\0’„&“u©rhEÉœß#éE©zªFÂ6ÑŸŠ.b>@Ã¦åò`úå¦¼™\0£Õ½\Zw \\ª¸ÈQ{oCÉndvV¹¤ùhûu*Pâ¥uoWNè¦J‘àŠÌ\\™IL¼ÒM.A¤Xí¼ò…+zõ‰ŸÒp/}G2;¾\Z§^j/fEá?`	ùo:MÃ	›‹§tQ\'&K>#Ô”zPÎ˜½Ð\0\"ú\\u·þwÑæ..Œ]u u††©j=-tÖ\"u‚y\Z/ƒ‘—Pc‡»ÜÍ(‡eÓµ¾+xüañÐ|eLMw\'©µËçÏÉŸ4yV±/WïÙ-¡è\ZE“b#t½uUtóed]z|œXmwrŸª…ÁÉ|¡qòYÈZñ¯¾òE¦GäÁã„GÚyNŽb¹Íš&oÒ¯³±ÊÚmÈ UH|4ðŽ¢ôšlIQV-<B7À¸B\rÇÍÿPVE\'ÁsCæZØŽ\"ƒ’\rêØ^”i\rˆü¸±¤ÍöpÊX†fºÑ$Z2|•	‰~]Yûí,ó+äpúIà31´øŒ2Õmxq5“7²Gú­e`ÀxÛßÂå/Íä-O,êÐ\r>DÁ–Éùš4T´)_`÷ÿ½?62C«†{£°‰€œ6ªnðgÈ¥=µÖ:$×Ô/ó¯§»šj0arøÂõM\"|cÒVlBºœ\\Ì\0RÈé«ErF\rrÀ³üz”ýÄÙBQŸªC3…I’8è¾üÀÜŠŠÍÔÐêì×Ç5fÜª\rf¼P…»mC%eUÐŸŒÍø:…Ž/$ŒntÉt!gu¾257éÂ}@÷Ãƒ€\ZŠ\nþ31‚ò\n$e…†UmrÛ°þ>®ÓµIï_ q€Ë¿¸’¦Wwîü¶Ü«Óð ƒA‚@ Ú%V²æW­:Ø\'i‹øËù¸ÔmÖ!«£UÑi”Lø†6G\Z,èlƒÁø#µž×¦Ã–ï·\'Ð7cýà@÷’ü‡®{ÄÕù²Íuëo*óQÖÐ$ôÕ•àÌŽ¬`,”rp)G]EÈPýþu`ÇøCC5á©ý6•\nc•ÏUñ\"Žh7¨Qž°×Ä²ÖI›HªRWeÇ[ÿä/µè5‚ ôòÜ¸$Sôgi¯éLìÂ2Ty¸WAXî¹u\rö0´ï‘”áÛÈ\0p°=Mˆä\"-’ù¿¨\Z— ÏFÞ’\rÌ1krõŸ•ÿ|Ø,e­\\X­¡fèñð‡‡G÷ ã’ÃßÔ,!úL»q‰ªÖ“ibÂZëŸhÜÙæ×Tx-¤{GVtÈ~°få°À=O²ÿFM;GRì®6ž+Â™1wmTÍ¶±3«=R¿IaŽ>ÿFÚ–ª5rß³¨è•%o }â]ÌYÆSWìx*KW ¦ìßLa¶ z¶å!ºŠ2D²±¡…Y†p¶·U‡•;²¯#ã”Óx\rýÐZ)÷ñ^§ITO¼_A/Ý\ZýØœU­l#ÔßòËœ`x°ü6?odp\\dõJÓI´Ò— IôŠ¶4J;Íz||›$oº:pVi³\"é{¾q‰áÚq–ËMoÆ“¡ê¨tÊãrÈbd®ð?Óp\r<#|sVß0í4%úf·ëXšçßAŒTññÚŸì)þeô\"„Ù\ZÁ|ª‡]rd<hŸNÄD5çZ²ÑÃa%a²ÙlþÊ—[pÓ\'I‹B¶X‰„ûf¬~l6Óí‚G ðèV™-#ÜA,–×l±’¾Ðô¸\\â0»Uõ1%ólÆû·x\Z®èÎŒZÕ¥fÓ§Äim«Ð$Rùï¼`e[ë—fMímU‘#GýöEªw”K\"\nöŸÕ$2ìÊåÉýë—’¿oñ‘Qi‘¾+ÀÙç—sZ#£¶Œà7 s‰ðèë‰óy_7™ÐDÈÁ|\\÷â³»$êê@ÜÐw˜•OÆ¶Å›æh¥j»â²4¸³À;Õò;9$\r#œxÄ˜Aò•úªkâ2o~½Ôšî¤Ðµ+îá•–nz‡/þÝ³]\rïKŠÝ/\r•t!füuÒÔÿí¦èÓUD>¹9’,k}-ã>Ž0š¿Hgçˆ™nÉòÿòë1.ÕtðXggÁ‹ >7¿Ö/mF›B£¹³ywúÑŒa°‰ØI€‰OAöÚ~~¦éH7¾’¹Yã|•ëËwßC‡P~™&þbü\Z\\ ÃˆPÌaQÆINÅÎ-*b-ß=ß¢FÐ°;!ÑÌäŸÈ…€6‰\ZÖ‰?«ëcG“ÿÞ~t˜l;Ëg%¯H7ˆî”ÿu–Ã^ñ–´B\r5PsKŸS¡\nÍ’VWpŸðoIbjèòå1­ëö‹Æí?VoI\0E‘]—[vÃu°ó¯u¾õmÎfå!|¤žä\\lRÏGO‚K1½¦)~ýÌ\rK$•ÜòÄÅÕÏ–¦7oÒ	A°DZ	2KÌSx\"¬¬kto2{„3üÌ.‰|/úðæÅf9‚ç±þ‹¨†þ­§éºÜn9ò‹¬eÄ=4Ì-†µ­‡PLÅj^¹ž«', '2eeeaabbbvvvll111777oooaaabbvvvs');
INSERT INTO `sis_sessions_vars` VALUES ('ðœ¨ÇªZ³VB!üÆ8þ®¨', '…‘Ü›¤WÞ…Ì²“Ô0PÒ«·¦4](pï2“ªÊß}úi§E\0~¬© \\ñžBrw9F·6?WÒ€c2Ê$PÆ.j­ýK {”w>', 'db999ll111666ddmmm333ffwwwyyyttt');
INSERT INTO `sis_sessions_vars` VALUES ('…÷&þ¡½Ú™a w‡', '·-dÔ±Lÿ-Ïvm§qjÀž', 'db999ll111666ddmmm333ffwwwyyyttt');
INSERT INTO `sis_sessions_vars` VALUES ('[ä ó¾éF¼¥[ò“M	-Œ', 'y\"Ãºlƒ,„ù®w5\r+d', 'db999ll111666ddmmm333ffwwwyyyttt');
INSERT INTO `sis_sessions_vars` VALUES ('äúfæ\Z\0G´üùUB', '3N¿>S–Ï›¬Ö\Zù!šrê:-\'}[ûàïûÉõzw*', 'db999ll111666ddmmm333ffwwwyyyttt');
INSERT INTO `sis_sessions_vars` VALUES ('^Á3\0è×fø\0[.UN', '19Ö¬àø>%„%ÃzÃåüÎ', 'db999ll111666ddmmm333ffwwwyyyttt');
INSERT INTO `sis_sessions_vars` VALUES ('ˆe`òuéUfõêcrOú', '‹ ›.¦v§Q\n:Ø	Ê', 'db999ll111666ddmmm333ffwwwyyyttt');
INSERT INTO `sis_sessions_vars` VALUES ('ïíÓê1%ådþ½ù;¦', 'Q~‹((‘w’úyeÁ‘i»', 'db999ll111666ddmmm333ffwwwyyyttt');
INSERT INTO `sis_sessions_vars` VALUES ('#]N„S‰Ó\\	Ð» ×', '\rîM÷„ÅÒØpáªKÀåâÀÁíNb,0`R¶`òiÙ&4JC&Õ«Áxº,S5Ë›uˆi\n„HšNµˆMÍÙK‡*¶ŒFúïîâÿÍ’', 'db999ll111666ddmmm333ffwwwyyyttt');
INSERT INTO `sis_sessions_vars` VALUES ('ó}ß}ª“åHngåål', '¯\rÖþŽf¹¸¼4Ò¡Îs\"ÏwÚeµiÖ@0ú<õ°&<´zWì\'¤‚©\rxgÜ?MÓû…êœD„o3aÖÓð§–4.×NbSpÊû‰HfLËï}çLH?FAñ‹ëÞÇêïÅÚ@b\nëO–ˆzXN•—>nÀ\Zôòö„€TÌˆ)Ö¾¨áMlÖ;2mÍŠåóá60&»ï\'uŒò/Ìî¥]jRÓ„fñŠ|-·É0gòÔR¿Ô4ÀÅPçØh\rÁ‹¥‡³öL¿ ÕY–¡1N˜v­oŠóPÒ³8R_Œõ&3—qEÌË-¹5¸|˜-÷‰Ü?Iî °øXô™œ]kf ”nç›lp=UcºA,þÂ,ñíô‡©â^ie·²cõ2¼eú²ë!{è½ŒR”ô&úð-Å<¾ iŽö#d#âp¿„û(­J›­0úíªm-Lnðÿù^sŽrí¸ª\0Cë¤;s£öb]å¨ðÿ=/Ýuvƒ5¡µ¥){ç›d¼Q}OaÜAA³ï;úË-rÃ‘»aÎ_`Öù‹(ìMß<üU3«3yL~½¡ù&iûÐ…»RŠ™Üé-“ã;…úÿ}Ap…7Æ†·ÒIcwLç€47ÖS-¾€Š\Zº¨]Ó@\naù±éOlbzgÛæj³9KŸæ¦d¸6Là:ÛfgÁÉ1ë	å(×#+ÞúÁ11ö„€-\'ç˜÷.LãE\Z+	‹B„w!Ð0S9«*Îa™&ûC­x\"ªØ©P39=ŸaPæäŒæ~aª*Ÿa6—b½×k†;Ùi+ö\"0sõh?ÑáõÚJèˆ)óÓZ€ì[Øl¢ü–ÖtÅÆB™¯âŠmër¾°.‹:‰Hœêwü±øL«.(¾‘L”é\r ¥aà½¦Å´Ï+’-S’^Uot&.ä`p_+ÕVü©‰=7ÒË¬óH\rp1:„ þÌ<Úøc¶ªáðõSµ\ZÂÌÕ;FfÛ ­’4{?ÝÕÐÒ§weVò{4u¾Û£¼øz3ÃÞ„Žæƒ\\çdÛE^+ï¸{ýOí±<vmöÄÃ)’¢ó|pý´û…2gSV‹´5~%æM— šÃoò´éãeTr€Ï£ôÜ¯ª´†</Ð!\rãÊ€~‡õì”µÍ†’IdæT‹Š«ãUW	}_³9ðOèYªÜhÎŽ\nãi7ù<y\nùŽ\nØ±êùŽ¶³†úÍ`gi¶ƒû‹u‹[‘©º‹2X³<›¨¢Îa³OÞäOPÏSÐžû%™þfh…Œ	T0ÍÎêh«È—Ò˜ÌªpÄ£&9Rè\'g96; Œ{„‰/\0ÀHàáóÙ·ÄòTq\Z9Ñú7¡•—ùš5ìL{BÛ­6e:i¬ÿ5Œ/H3-]s¥vì/’\"êùˆå“cÁ7ô~Š¯Q$ËFÓpcköœ @F•à:LŒÎi0¦/‚0ä´ûz´}Û­ë5Å«3fÏ·«ù¬2: 4¦³õ³Kñ§­Ç¿Ò¾Ù‚Æ\\ÞÆ¾ò»:‹?X°¯ÈÒælÇl\Zîçþ\rl>#ûfŒ©Ï)Ÿ$pY:ó˜kLgQø1’ùÏ&ø\Z+Id‹[OE¢;}\'³¹Òì÷u\\¼ñÉhÇË4rý´3\0’„&“u©rhEÉœß#éE©zªFÂ6ÑŸŠ.b>@Ã¦åò`úå¦¼™\0£Õ½\Zw \\ª¸ÈQ{oCÉndvV¹¤ùhûu*Pâ¥uoWNè¦J‘àŠÌ\\™IL¼ÒM.A¤Xí¼ò…+zõ‰ŸÒp/}G2;¾\Z§^j/fEá?`	ùo:MÃ	›‹§tQ\'&K>#Ô”zPÎ˜½Ð\0\"ú\\u·þwÑæ..Œ]u u††©j=-tÖ\"u‚y\Z/ƒ‘—Pc‡»ÜÍ(‡eÓµ¾+xüañÐ|eLMw\'©µËçÏÉŸ4yV±/WïÙ-¡è\ZE“b#t½uUtóed]z|œXmwrŸª…ÁÉ|¡qòYÈZñ¯¾òE¦GäÁã„GÚyNŽb¹Íš&oÒ¯³±ÊÚmÈ UH|4ðŽ¢ôšlIQV-<B7À¸B\rÇÍÿPVE\'ÁsCæZØŽ\"ƒ’\rêØ^”i\rˆü¸±¤ÍöpÊX†fºÑ$Z2|•	‰~]Yûí,ó+äpúIà31´øŒ2Õmxq5“7²Gú­e`ÀxÛßÂå/Íä-O,êÐ\r>DÁ–Éùš4T´)_`÷ÿ½?62C«†{£°‰€œ6ªnðgÈ¥=µÖ:$×Ô/ó¯§»šj0arøÂõM\"|cÒVlBºœ\\Ì\0RÈé«ErF\rrÀ³üz”ýÄÙBQŸªC3…I’8è¾üÀÜŠŠÍÔÐêì×Ç5fÜª\rf¼P…»mC%eUÐŸŒÍø:…Ž/$ŒntÉt!gu¾257éÂ}@÷Ãƒ€\ZŠ\nþ31‚ò\n$e…†UmrÛ°þ>®ÓµIï_ q€Ë¿¸’¦Wwîü¶Ü«Óð ƒA‚@ Ú%V²æW­:Ø\'i‹øËù¸ÔmÖ!«£UÑi”Lø†6G\Z,èlƒÁø#µž×¦Ã–ï·\'Ð7cýà@÷’ü‡®{ÄÕù²Íuëo*óQÖÐ$ôÕ•àÌŽ¬`,”rp)G]EÈPýþu`ÇøCC5á©ý6•\nc•ÏUñ\"Žh7¨Qž°×Ä²ÖI›HªRWeÇ[ÿä/µè5‚ ôòÜ¸$Sôgi¯éLìÂ2Ty¸WAXî¹u\rö0´ï‘”áÛÈ\0p°=Mˆä\"-’ù¿¨\Z— ÏFÞ’\rÌ1krõŸ•ÿ|Ø,e­\\X­¡fèñð‡‡G÷ ã’ÃßÔ,!úL»q‰ªÖ“ibÂZëŸhÜÙæ×Tx-¤{GVtÈ~°få°À=O²ÿFM;GRì®6ž+Â™1wmTÍ¶±3«=R¿IaŽ>ÿFÚ–ª5rß³¨è•%o }â]ÌYÆSWìx*KW ¦ìßLa¶ z¶å!ºŠ2D²±¡…Y†p¶·U‡•;²¯#ã”Óx\rýÐZ)÷ñ^§ITO¼_A/Ý\ZýØœU­l#ÔßòËœ`x°ü6?odp\\dõJÓI´Ò— IôŠ¶4J;Íz||›$oº:pVi³\"é{¾q‰áÚq–ËMoÆ“¡ê¨tÊãrÈbd®ð?Óp\r<#|sVß0í4%úf·ëXšçßAŒTññÚŸì)þeô\"„Ù\ZÁ|ª‡]rd<hŸNÄD5çZ²ÑÃa%a²ÙlþÊ—[pÓ\'I‹B¶X‰„ûf¬~l6Óí‚G ðèV™-#ÜA,–×l±’¾Ðô¸\\â0»Uõ1%ólÆû·x\Z®èÎŒZÕ¥fÓ§Äim«Ð$Rùï¼`e[ë—fMímU‘#GýöEªw”K\"\nöŸÕ$2ìÊåÉýë—’¿oñ‘Qi‘¾+ÀÙç—sZ#£¶Œà7 s‰ðèë‰óy_7™ÐDÈÁ|\\÷â³»$êê@ÜÐw˜•OÆ¶Å›æh¥j»â²4¸³À;Õò;9$\r#œxÄ˜Aò•úªkâ2o~½Ôšî¤Ðµ+îá•–nz‡/þÝ³]\rïKŠÝ/\r•t!füuÒÔÿí¦èÓUD>¹9’,k}-ã>Ž0š¿Hgçˆ™nÉòÿòë1.ÕtðXggÁ‹ >7¿Ö/mF›B£¹³ywúÑŒa°‰ØI€‰OAöÚ~~¦éH7¾’¹Yã|•ëËwßC‡P~™&þbü\Z\\ ÃˆPÌaQÆINÅÎ-*b-ß=ß¢FÐ°;!ÑÌäŸÈ…€6‰\ZÖ‰?«ëcG“ÿÞ~t˜l;Ëg%¯H7ˆî”ÿu–Ã^ñ–´B\r5PsKŸS¡\nÍ’VWpŸðoIbjèòå1­ëö‹Æí?VoI\0E‘]—[vÃu°ó¯u¾õmÎfå!|¤žä\\lRÏGO‚K1½¦)~ýÌ\rK$•ÜòÄÅÕÏ–¦7oÒ	A°DZ	2KÌSx\"¬¬kto2{„3üÌ.‰|/úðæÅf9‚ç±þ‹¨†þ­§éºÜn9ò‹¬eÄ=4Ì-†µ­‡PLÅj^¹ž«', 'db999ll111666ddmmm333ffwwwyyyttt');

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
CREATE DEFINER=`root`@`localhost` PROCEDURE `insert_log`(IN `p_id_user` int,IN `p_detail` blob,OUT `res` tinyint)
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
		INSERT INTO `sis_log`(id_user,date_bi,detail) VALUES (p_id_user,now(),p_detail);
	COMMIT;
	
	-- SUCCESS
	SET res = 0;
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
CREATE DEFINER=`root`@`localhost` PROCEDURE `insert_user`(IN `p_id` varchar(50),IN `p_nombre` varchar(150),IN `p_email` varchar(100),IN `p_telefono` varchar(15),IN `p_id_tipo_tel` varchar(1),IN `p_id_roll` int,IN `p_pass` varchar(50),OUT `res` TINYINT  UNSIGNED)
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
CREATE DEFINER=`root`@`localhost` PROCEDURE `update_perfil`(IN `p_id` varchar(50),IN `p_email` varchar(100),IN `p_telefono` varchar(15),IN `p_id_tipo_tel` varchar(1),IN `p_pass` varchar(50),OUT `res` TINYINT  UNSIGNED)
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
CREATE DEFINER=`root`@`localhost` FUNCTION `checklogin`(`p_id` varchar(50),`p_pass` varchar(50)) RETURNS int(1)
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
