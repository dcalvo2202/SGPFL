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

-- San José
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
('1','01','10','Hatillo'),
('1','01','11','San Sebastián');

-- Escazú
INSERT INTO `sis_distrito` VALUES
('1','02','01','Escazú'),
('1','02','02','San Antonio'),
('1','02','03','San Rafael');

-- Desamparados
INSERT INTO `sis_distrito` VALUES
('1','03','01','Desamparados'),
('1','03','02','San Miguel'),
('1','03','03','San Juan de Dios'),
('1','03','04','San Rafael Arriba'),
('1','03','05','San Antonio'),
('1','03','06','Frailes'),
('1','03','07','Patarrá'),
('1','03','08','San Cristóbal'),
('1','03','09','Rosario'),
('1','03','10','Damas'),
('1','03','11','San Rafael Abajo'),
('1','03','12','Gravilias'),
('1','03','13','Los Guido');

-- Puriscal
INSERT INTO `sis_distrito` VALUES
('1','04','01','Santiago'),
('1','04','02','Mercedes Sur'),
('1','04','03','Barbacoas'),
('1','04','04','Grifo Alto'),
('1','04','05','San Rafael'),
('1','04','06','Candelarita'),
('1','04','07','Desamparaditos'),
('1','04','08','San Antonio'),
('1','04','09','Chires');

-- Tarrazú
INSERT INTO `sis_distrito` VALUES
('1','05','01','San Marcos'),
('1','05','02','San Lorenzo'),
('1','05','03','San Carlos');

-- Aserrí
INSERT INTO `sis_distrito` VALUES
('1','06','01','Aserrí'),
('1','06','02','Tarbaca'),
('1','06','03','Vuelta de Jorco'),
('1','06','04','San Gabriel'),
('1','06','05','Legua'),
('1','06','06','Monterrey'),
('1','06','07','Salitrillos');

-- Mora
INSERT INTO `sis_distrito` VALUES
('1','07','01','Colón'),
('1','07','02','Guayabo'),
('1','07','03','Tabarcia'),
('1','07','04','Piedras Negras'),
('1','07','05','Picagres');

-- Goicoechea
INSERT INTO `sis_distrito` VALUES
('1','08','01','Guadalupe'),
('1','08','02','San Francisco'),
('1','08','03','Calle Blancos'),
('1','08','04','Mata de Plátano'),
('1','08','05','Ipís'),
('1','08','06','Rancho Redondo'),
('1','08','07','Purral');

-- Santa Ana
INSERT INTO `sis_distrito` VALUES
('1','09','01','Santa Ana'),
('1','09','02','Salitral'),
('1','09','03','Pozos'),
('1','09','04','Uruca'),
('1','09','05','Piedades'),
('1','09','06','Brasil');

-- Alajuelita
INSERT INTO `sis_distrito` VALUES
('1','10','01','Alajuelita'),
('1','10','02','San Josecito'),
('1','10','03','San Antonio'),
('1','10','04','Concepción'),
('1','10','05','San Felipe');

-- Vásquez de Coronado
INSERT INTO `sis_distrito` VALUES
('1','11','01','San Isidro'),
('1','11','02','San Rafael'),
('1','11','03','Dulce Nombre de Jesús'),
('1','11','04','Patalillo'),
('1','11','05','Cascajal');

-- Acosta
INSERT INTO `sis_distrito` VALUES
('1','12','01','San Ignacio'),
('1','12','02','Guaitil'),
('1','12','03','Palmichal'),
('1','12','04','Cangrejal'),
('1','12','05','Sabanillas');

-- Tibás
INSERT INTO `sis_distrito` VALUES
('1','13','01','San Juan'),
('1','13','02','Cinco Esquinas'),
('1','13','03','Anselmo Llorente'),
('1','13','04','León XIII'),
('1','13','05','Colima');

-- Moravia
INSERT INTO `sis_distrito` VALUES
('1','14','01','San Vicente'),
('1','14','02','San Jerónimo'),
('1','14','03','La Trinidad');

-- Montes de Oca
INSERT INTO `sis_distrito` VALUES
('1','15','01','San Pedro'),
('1','15','02','Sabanilla'),
('1','15','03','Mercedes'),
('1','15','04','San Rafael');

-- Turrubares
INSERT INTO `sis_distrito` VALUES
('1','16','01','San Pablo'),
('1','16','02','San Pedro'),
('1','16','03','San Juan de Mata'),
('1','16','04','San Luis'),
('1','16','05','Carara');

-- Dota
INSERT INTO `sis_distrito` VALUES
('1','17','01','Santa María'),
('1','17','02','Jardín'),
('1','17','03','Copey');

-- Curridabat
INSERT INTO `sis_distrito` VALUES
('1','18','01','Curridabat'),
('1','18','02','Granadilla'),
('1','18','03','Sánchez'),
('1','18','04','Tirrases');

-- Pérez Zeledón
INSERT INTO `sis_distrito` VALUES
('1','19','01','San Isidro de El General'),
('1','19','02','El General'),
('1','19','03','Daniel Flores'),
('1','19','04','Rivas'),
('1','19','05','San Pedro'),
('1','19','06','Platanares'),
('1','19','07','Pejibaye'),
('1','19','08','Cajón'),
('1','19','09','Barú'),
('1','19','10','Río Nuevo'),
('1','19','11','Páramo');

-- León Cortés
INSERT INTO `sis_distrito` VALUES
('1','20','01','San Pablo'),
('1','20','02','San Andrés'),
('1','20','03','Llano Bonito'),
('1','20','04','San Isidro'),
('1','20','05','Santa Cruz'),
('1','20','06','San Antonio');

--  Alajuela
INSERT INTO `sis_distrito` VALUES
('2','01','01','Alajuela'),
('2','01','02','San José'),
('2','01','03','Carrizal'),
('2','01','04','San Antonio'),
('2','01','05','Guácima'),
('2','01','06','San Isidro'),
('2','01','07','Sabanilla'),
('2','01','08','San Rafael'),
('2','01','09','Río Segundo'),
('2','01','10','Desamparados'),
('2','01','11','Turrúcares'),
('2','01','12','Tambor'),
('2','01','13','Garita'),
('2','01','14','Sarapiquí');

--  San Ramón
INSERT INTO `sis_distrito` VALUES
('2','02','01','San Ramón'),
('2','02','02','Santiago'),
('2','02','03','San Juan'),
('2','02','04','Piedades Norte'),
('2','02','05','Piedades Sur'),
('2','02','06','San Rafael'),
('2','02','07','San Isidro'),
('2','02','08','Ángeles'),
('2','02','09','Alfaro'),
('2','02','10','Volio'),
('2','02','11','Concepción'),
('2','02','12','Zapotal'),
('2','02','13','Peñas Blancas');

--  Grecia
INSERT INTO `sis_distrito` VALUES
('2','03','01','Grecia'),
('2','03','02','San Isidro'),
('2','03','03','San José'),
('2','03','04','San Roque'),
('2','03','05','Tacares'),
('2','03','06','Río Cuarto'),
('2','03','07','Puente de Piedra'),
('2','03','08','Bolívar');

-- San Mateo
INSERT INTO `sis_distrito` VALUES
('2','04','01','San Mateo'),
('2','04','02','Desmonte'),
('2','04','03','Jesús María'),
('2','04','04','Labrador');

-- Atenas
INSERT INTO `sis_distrito` VALUES
('2','05','01','Atenas'),
('2','05','02','Jesús'),
('2','05','03','Mercedes'),
('2','05','04','San Isidro'),
('2','05','05','Concepción'),
('2','05','06','San José'),
('2','05','07','Santa Eulalia'),
('2','05','08','Escobal');

--  Naranjo
INSERT INTO `sis_distrito` VALUES
('2','06','01','Naranjo'),
('2','06','02','San Miguel'),
('2','06','03','San José'),
('2','06','04','Cirrí Sur'),
('2','06','05','San Jerónimo'),
('2','06','06','San Juan'),
('2','06','07','El Rosario'),
('2','06','08','Palmitos');

-- Palmares
INSERT INTO `sis_distrito` VALUES
('2','07','01','Palmares'),
('2','07','02','Zaragoza'),
('2','07','03','Buenos Aires'),
('2','07','04','Santiago'),
('2','07','05','Candelaria'),
('2','07','06','Esquipulas'),
('2','07','07','La Granja');

-- Poás
INSERT INTO `sis_distrito` VALUES
('2','08','01','San Pedro'),
('2','08','02','San Juan'),
('2','08','03','San Rafael'),
('2','08','04','Carrillos'),
('2','08','05','Sabana Redonda');

-- Orotina
INSERT INTO `sis_distrito` VALUES
('2','09','01','Orotina'),
('2','09','02','El Mastate'),
('2','09','03','Hacienda Vieja'),
('2','09','04','Coyolar'),
('2','09','05','La Ceiba');

-- San Carlos
INSERT INTO `sis_distrito` VALUES
('2','10','01','Quesada'),
('2','10','02','Florencia'),
('2','10','03','Buenavista'),
('2','10','04','Aguas Zarcas'),
('2','10','05','Venecia'),
('2','10','06','Pital'),
('2','10','07','La Fortuna'),
('2','10','08','La Tigra'),
('2','10','09','La Palmera'),
('2','10','10','Venado'),
('2','10','11','Cutris'),
('2','10','12','Monterrey'),
('2','10','13','Pocosol');

-- Zarcero
INSERT INTO `sis_distrito` VALUES
('2','11','01','Zarcero'),
('2','11','02','Laguna'),
('2','11','03','Tapesco'),
('2','11','04','Guadalupe'),
('2','11','05','Palmira'),
('2','11','06','Zapote'),
('2','11','07','Brisas');

-- Valverde Vega
INSERT INTO `sis_distrito` VALUES
('2','12','01','Sarchí Norte'),
('2','12','02','Sarchí Sur'),
('2','12','03','Toro Amarillo'),
('2','12','04','San Pedro'),
('2','12','05','Rodríguez');

-- Upala
INSERT INTO `sis_distrito` VALUES
('2','13','01','Upala'),
('2','13','02','Aguas Claras'),
('2','13','03','San José'),
('2','13','04','Bijagua'),
('2','13','05','Delicias'),
('2','13','06','Dos Ríos'),
('2','13','07','Yolillal');

-- Los Chiles
INSERT INTO `sis_distrito` VALUES
('2','14','01','Los Chiles'),
('2','14','02','Caño Negro'),
('2','14','03','El Amparo'),
('2','14','04','San Jorge');

-- Guatuso
INSERT INTO `sis_distrito` VALUES
('2','15','01','San Rafael'),
('2','15','02','Buenavista'),
('2','15','03','Cote'),
('2','15','04','Katira');

-- Río Cuarto
INSERT INTO `sis_distrito` VALUES
('2','16','01','Río Cuarto'),
('2','16','02','Santa Rita'),
('2','16','03','Santa Isabel');

--  Cartago
INSERT INTO `sis_distrito` VALUES
('3','01','01','Oriental'),
('3','01','02','Occidental'),
('3','01','03','Carmen'),
('3','01','04','San Nicolás'),
('3','01','05','Aguacaliente'),
('3','01','06','Guadalupe'),
('3','01','07','Corralillo'),
('3','01','08','Tierra Blanca'),
('3','01','09','Dulce Nombre'),
('3','01','10','Llano Grande'),
('3','01','11','Quebradilla');

--  Paraíso
INSERT INTO `sis_distrito` VALUES
('3','02','01','Paraíso'),
('3','02','02','Santiago'),
('3','02','03','Orosi'),
('3','02','04','Cachí'),
('3','02','05','Llanos de Santa Lucía');

--  La Unión
INSERT INTO `sis_distrito` VALUES
('3','03','01','Tres Ríos'),
('3','03','02','San Diego'),
('3','03','03','San Juan'),
('3','03','04','San Rafael'),
('3','03','05','Concepción'),
('3','03','06','Dulce Nombre'),
('3','03','07','San Ramón'),
('3','03','08','Río Azul');

-- Jiménez
INSERT INTO `sis_distrito` VALUES
('3','04','01','Juan Viñas'),
('3','04','02','Tucurrique'),
('3','04','03','Pejibaye');

-- Turrialba
INSERT INTO `sis_distrito` VALUES
('3','05','01','Turrialba'),
('3','05','02','La Suiza'),
('3','05','03','Peralta'),
('3','05','04','Santa Cruz'),
('3','05','05','Santa Rosa'),
('3','05','06','Pavones'),
('3','05','07','Tuis'),
('3','05','08','Tayutic'),
('3','05','09','Santa Teresita'),
('3','05','10','La Isabel'),
('3','05','11','Chirripó');

--  Alvarado
INSERT INTO `sis_distrito` VALUES
('3','06','01','Pacayas'),
('3','06','02','Cervantes'),
('3','06','03','Capellades');

-- Oreamuno
INSERT INTO `sis_distrito` VALUES
('3','07','01','San Rafael'),
('3','07','02','Cot'),
('3','07','03','Potrero Cerrado'),
('3','07','04','Cipreses'),
('3','07','05','Santa Rosa');

-- El Guarco
INSERT INTO `sis_distrito` VALUES
('3','08','01','El Tejar'),
('3','08','02','San Isidro'),
('3','08','03','Tobosi'),
('3','08','04','Patio de Agua');

--  Heredia
INSERT INTO `sis_distrito` VALUES
('4','01','01','Heredia'),
('4','01','02','Mercedes'),
('4','01','03','San Francisco'),
('4','01','04','Ulloa'),
('4','01','05','Varablanca');

--  Barva
INSERT INTO `sis_distrito` VALUES
('4','02','01','Barva'),
('4','02','02','San Pedro'),
('4','02','03','San Pablo'),
('4','02','04','San Roque'),
('4','02','05','Santa Lucía'),
('4','02','06','San José de la Montaña');

--  Santo Domingo
INSERT INTO `sis_distrito` VALUES
('4','03','01','Santo Domingo'),
('4','03','02','San Vicente'),
('4','03','03','San Miguel'),
('4','03','04','Paracito'),
('4','03','05','Santo Tomás'),
('4','03','06','Santa Rosa'),
('4','03','07','Tures'),
('4','03','08','Pará');

-- Santa Bárbara
INSERT INTO `sis_distrito` VALUES
('4','04','01','Santa Bárbara'),
('4','04','02','San Pedro'),
('4','04','03','San Juan'),
('4','04','04','Jesús'),
('4','04','05','Santo Domingo'),
('4','04','06','Purabá');

-- San Rafael
INSERT INTO `sis_distrito` VALUES
('4','05','01','San Rafael'),
('4','05','02','San Josecito'),
('4','05','03','Santiago'),
('4','05','04','Ángeles'),
('4','05','05','Concepción');

--  San Isidro
INSERT INTO `sis_distrito` VALUES
('4','06','01','San Isidro'),
('4','06','02','San José'),
('4','06','03','Concepción'),
('4','06','04','San Francisco');

-- Belén
INSERT INTO `sis_distrito` VALUES
('4','07','01','San Antonio'),
('4','07','02','La Ribera'),
('4','07','03','La Asunción');

-- Flores
INSERT INTO `sis_distrito` VALUES
('4','08','01','San Joaquín'),
('4','08','02','Barrantes'),
('4','08','03','Llorente');

-- San Pablo
INSERT INTO `sis_distrito` VALUES
('4','09','01','San Pablo'),
('4','09','02','Rincón de Sabanilla');

-- Sarapiquí
INSERT INTO `sis_distrito` VALUES
('4','10','01','Puerto Viejo'),
('4','10','02','La Virgen'),
('4','10','03','Horquetas'),
('4','10','04','Llanuras del Gaspar'),
('4','10','05','Cureña');

--  Liberia
INSERT INTO `sis_distrito` VALUES
('5','01','01','Liberia'),
('5','01','02','Cañas Dulces'),
('5','01','03','Mayorga'),
('5','01','04','Nacascolo'),
('5','01','05','Curubandé');

--  Nicoya
INSERT INTO `sis_distrito` VALUES
('5','02','01','Nicoya'),
('5','02','02','Mansión'),
('5','02','03','San Antonio'),
('5','02','04','Quebrada Honda'),
('5','02','05','Sámara'),
('5','02','06','Nosara'),
('5','02','07','Belén de Nosarita');

--  Santa Cruz
INSERT INTO `sis_distrito` VALUES
('5','03','01','Santa Cruz'),
('5','03','02','Bolsón'),
('5','03','03','Veintisiete de Abril'),
('5','03','04','Tempate'),
('5','03','05','Cartagena'),
('5','03','06','Cuajiniquil'),
('5','03','07','Diriá'),
('5','03','08','Cabo Velas'),
('5','03','09','Tamarindo');

-- Bagaces
INSERT INTO `sis_distrito` VALUES
('5','04','01','Bagaces'),
('5','04','02','La Fortuna'),
('5','04','03','Mogote'),
('5','04','04','Río Naranjo');

-- Carrillo
INSERT INTO `sis_distrito` VALUES
('5','05','01','Filadelfia'),
('5','05','02','Palmira'),
('5','05','03','Sardinal'),
('5','05','04','Belén');

--  Cañas
INSERT INTO `sis_distrito` VALUES
('5','06','01','Cañas'),
('5','06','02','Palmira'),
('5','06','03','San Miguel'),
('5','06','04','Bebedero'),
('5','06','05','Porozal');

-- Abangares
INSERT INTO `sis_distrito` VALUES
('5','07','01','Las Juntas'),
('5','07','02','Sierra'),
('5','07','03','San Juan'),
('5','07','04','Colorado');

-- Tilarán
INSERT INTO `sis_distrito` VALUES
('5','08','01','Tilarán'),
('5','08','02','Quebrada Grande'),
('5','08','03','Tronadora'),
('5','08','04','Santa Rosa'),
('5','08','05','Líbano'),
('5','08','06','Tierras Morenas'),
('5','08','07','Arenal'),
('5','08','08','Cabeceras');

-- Nandayure
INSERT INTO `sis_distrito` VALUES
('5','09','01','Carmona'),
('5','09','02','Santa Rita'),
('5','09','03','Zapotal'),
('5','09','04','San Pablo'),
('5','09','05','Porvenir'),
('5','09','06','Bejuco');

-- La Cruz
INSERT INTO `sis_distrito` VALUES
('5','10','01','La Cruz'),
('5','10','02','Santa Cecilia'),
('5','10','03','La Garita'),
('5','10','04','Santa Elena');

-- Hojancha
INSERT INTO `sis_distrito` VALUES
('5','11','01','Hojancha'),
('5','11','02','Monte Romo'),
('5','11','03','Puerto Carrillo'),
('5','11','04','Huacas'),
('5','11','05','Matambú');

--  Puntarenas
INSERT INTO `sis_distrito` VALUES
('6','01','01','Puntarenas'),
('6','01','02','Pitahaya'),
('6','01','03','Chomes'),
('6','01','04','Lepanto'),
('6','01','05','Paquera'),
('6','01','06','Manzanillo'),
('6','01','07','Guacimal'),
('6','01','08','Barranca'),
('6','01','09','Monte Verde'),
('6','01','10','Isla del Coco'),
('6','01','11','Cobano'),
('6','01','12','Chacarita'),
('6','01','13','Chira'),
('6','01','14','Acapulco'),
('6','01','15','El Roble'),
('6','01','16','Arancibia');

--  Esparza
INSERT INTO `sis_distrito` VALUES
('6','02','01','Espíritu Santo'),
('6','02','02','San Juan Grande'),
('6','02','03','Macacona'),
('6','02','04','San Rafael'),
('6','02','05','San Jerónimo'),
('6','02','06','Caldera');

--  Buenos Aires
INSERT INTO `sis_distrito` VALUES
('6','03','01','Buenos Aires'),
('6','03','02','Volcán'),
('6','03','03','Potrero Grande'),
('6','03','04','Boruca'),
('6','03','05','Pilas'),
('6','03','06','Colinas'),
('6','03','07','Chánguena'),
('6','03','08','Biolley'),
('6','03','09','Brunka');

-- Montes de Oro
INSERT INTO `sis_distrito` VALUES
('6','04','01','Miramar'),
('6','04','02','La Unión'),
('6','04','03','San Isidro');

-- Osa
INSERT INTO `sis_distrito` VALUES
('6','05','01','Ciudad Cortés'),
('6','05','02','Palmar'),
('6','05','03','Sierpe'),
('6','05','04','Bahía Ballena'),
('6','05','05','Piedras Blancas');

--  Quepos
INSERT INTO `sis_distrito` VALUES
('6','06','01','Quepos'),
('6','06','02','Savegre'),
('6','06','03','Naranjito');

-- Golfito
INSERT INTO `sis_distrito` VALUES
('6','07','01','Golfito'),
('6','07','02','Puerto Jiménez'),
('6','07','03','Guaycará'),
('6','07','04','Pavón');

-- Coto Brus
INSERT INTO `sis_distrito` VALUES
('6','08','01','San Vito'),
('6','08','02','Sabalito'),
('6','08','03','Aguabuena'),
('6','08','04','Limoncito'),
('6','08','05','Pittier');

-- Parrita
INSERT INTO `sis_distrito` VALUES
('6','09','01','Parrita');

-- Corredores
INSERT INTO `sis_distrito` VALUES
('6','10','01','Corredor'),
('6','10','02','La Cuesta'),
('6','10','03','Canoas'),
('6','10','04','Laurel');

-- Garabito
INSERT INTO `sis_distrito` VALUES
('6','11','01','Jacó'),
('6','11','02','Tárcoles');

--  Limón
INSERT INTO `sis_distrito` VALUES
('7','01','01','Limón'),
('7','01','02','Valle La Estrella'),
('7','01','03','Río Blanco'),
('7','01','04','Matama');

--  Pococí
INSERT INTO `sis_distrito` VALUES
('7','02','01','Guápiles'),
('7','02','02','Jiménez'),
('7','02','03','La Rita'),
('7','02','04','Roxana'),
('7','02','05','Cariari'),
('7','02','06','Colorado'),
('7','02','07','La Colonia');

--  Siquirres
INSERT INTO `sis_distrito` VALUES
('7','03','01','Siquirres'),
('7','03','02','Pacuarito'),
('7','03','03','Florida'),
('7','03','04','Germania'),
('7','03','05','El Cairo'),
('7','03','06','Alegría'),
('7','03','07','Reventazón');

-- Talamanca
INSERT INTO `sis_distrito` VALUES
('7','04','01','Bratsi'),
('7','04','02','Sixaola'),
('7','04','03','Cahuita'),
('7','04','04','Telire');

-- Matina
INSERT INTO `sis_distrito` VALUES
('7','05','01','Matina'),
('7','05','02','Batán'),
('7','05','03','Carrandi');

--  Guácimo
INSERT INTO `sis_distrito` VALUES
('7','06','01','Guácimo'),
('7','06','02','Mercedes'),
('7','06','03','Pocora'),
('7','06','04','Río Jiménez'),
('7','06','05','Duacarí');

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
-- Asesor externo
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
-- Asesor externo
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
INSERT INTO `sis_rolls` VALUES ('3', 'CTFG', 'Comisión de trabajos finales de graduación tiene permisos de lectura sobre los documentos de los estudiantes y puede aprobar o rechazar los trabajos finales de graduación');
INSERT INTO `sis_rolls` VALUES ('4', 'Estudiante', 'Estudiantes de la universidad tiene permisos de lectura y escritura sobre sus documentos y puede enviar solicitudes de trabajos finales de graduación');
INSERT INTO `sis_rolls` VALUES ('5', 'Asesor externo', 'Tiene acceso de lectura a los modulos del estudiante');

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

-- Permisos para Asesor externo
-- Permisos limitados para el rol Asesor externo (id_roll = 5)
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
  `tutor` varchar(50) NOT NULL,
  `asesor_1` varchar(50) NOT NULL,
  `asesor_2` varchar(50) NOT NULL,
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
(8, '116440018', '205610158', '206580363');



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
-- Table structure for `tfg_proposals`
-- ----------------------------
DROP TABLE IF EXISTS `tfg_proposals`;
CREATE TABLE `tfg_proposals` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
    title VARCHAR(255) UNIQUE NOT NULL,
    disciplines VARCHAR(255) NOT NULL,
    document LONGBLOB NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    file_size INT UNSIGNED NOT NULL,
    status ENUM('Pendiente de Revisión', 'Cumple requisitos', 'No cumple requisitos') DEFAULT 'Pendiente de Revisión',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES sis_user(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

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
-- TABLA 2: PROPUESTAS TFG (ESTRUCTURA CORREGIDA)
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
  PRIMARY KEY (`id`),
  KEY `idx_upload_date` (`upload_date`),
  KEY `fk_tfg_files_user` (`uploaded_by`),
  CONSTRAINT `fk_tfg_files_user` FOREIGN KEY (`uploaded_by`) REFERENCES `sis_user` (`id`) ON UPDATE CASCADE
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
