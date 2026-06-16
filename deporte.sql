/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-12.2.2-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: deporte
-- ------------------------------------------------------
-- Server version	12.2.2-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `apoyos`
--

DROP TABLE IF EXISTS `apoyos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `apoyos` (
  `id_apoyo` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tipo` varchar(60) NOT NULL,
  `monto` decimal(10,2) DEFAULT NULL,
  `promedio` decimal(5,2) DEFAULT NULL,
  `fecha` date DEFAULT NULL,
  `id_usuario` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id_apoyo`),
  KEY `idx_apoyos_usuario` (`id_usuario`),
  CONSTRAINT `fk_apoyos_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
) ;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `apoyos`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `apoyos` WRITE;
/*!40000 ALTER TABLE `apoyos` DISABLE KEYS */;
/*!40000 ALTER TABLE `apoyos` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `categoria`
--

DROP TABLE IF EXISTS `categoria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `categoria` (
  `id_categoria` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tipo` varchar(30) NOT NULL,
  `grupos` varchar(60) DEFAULT NULL,
  PRIMARY KEY (`id_categoria`)
);
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categoria`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `categoria` WRITE;
/*!40000 ALTER TABLE `categoria` DISABLE KEYS */;
INSERT INTO `categoria` VALUES
(1,'Infantil','menores de 12'),
(2,'Juvenil','12 a 17'),
(3,'Libre','18 a 49'),
(4,'Veteranos','50 en adelante');
/*!40000 ALTER TABLE `categoria` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `contactos_emerg`
--

DROP TABLE IF EXISTS `contactos_emerg`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contactos_emerg` (
  `id_contacto` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nombre_completo` varchar(150) NOT NULL,
  `parentesco` varchar(60) NOT NULL,
  `tel_principal` varchar(20) NOT NULL,
  PRIMARY KEY (`id_contacto`)
);
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contactos_emerg`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `contactos_emerg` WRITE;
/*!40000 ALTER TABLE `contactos_emerg` DISABLE KEYS */;
INSERT INTO `contactos_emerg` VALUES
(1,'VIEJON','VIEJON','7224941321');
/*!40000 ALTER TABLE `contactos_emerg` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `datos_personales`
--

DROP TABLE IF EXISTS `datos_personales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `datos_personales` (
  `id_datos_personales` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `folio_unico` varchar(20) NOT NULL,
  `curp` char(18) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido_paterno` varchar(80) NOT NULL,
  `apellido_materno` varchar(80) DEFAULT NULL,
  `genero` enum('Masculino','Femenino','Prefiero no decir') NOT NULL,
  `fecha_nacimiento` date NOT NULL,
  `edad` tinyint(3) unsigned DEFAULT NULL,
  `menor_edad` tinyint(1) NOT NULL DEFAULT 0,
  `datos_tutor` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos_tutor`)),
  `domicilio` varchar(200) NOT NULL,
  `celular` varchar(20) NOT NULL,
  `correo` varchar(180) DEFAULT NULL,
  `id_usuario` int(10) unsigned NOT NULL,
  `id_delegacion` int(10) unsigned NOT NULL,
  `id_informacion_medica` int(10) unsigned DEFAULT NULL,
  `id_contacto` int(10) unsigned DEFAULT NULL,
  `id_seguro` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `categoria` varchar(20) GENERATED ALWAYS AS (case when `edad` < 12 then 'Infantil' when `edad` < 18 then 'Juvenil' when `edad` >= 50 then 'Veteranos' else 'Libre' end) VIRTUAL,
  PRIMARY KEY (`id_datos_personales`),
  UNIQUE KEY `uq_curp` (`curp`),
  UNIQUE KEY `uq_folio` (`folio_unico`),
  KEY `idx_dp_usuario` (`id_usuario`),
  KEY `idx_dp_delegacion` (`id_delegacion`),
  KEY `fk_dp_medica` (`id_informacion_medica`),
  KEY `fk_dp_contacto` (`id_contacto`),
  KEY `fk_dp_seguro` (`id_seguro`),
  CONSTRAINT `fk_dp_contacto` FOREIGN KEY (`id_contacto`) REFERENCES `contactos_emerg` (`id_contacto`),
  CONSTRAINT `fk_dp_delegacion` FOREIGN KEY (`id_delegacion`) REFERENCES `delegaciones` (`id_delegacion`),
  CONSTRAINT `fk_dp_medica` FOREIGN KEY (`id_informacion_medica`) REFERENCES `informacion_medica` (`id_informacion_medica`),
  CONSTRAINT `fk_dp_seguro` FOREIGN KEY (`id_seguro`) REFERENCES `seguro` (`id_seguro`),
  CONSTRAINT `fk_dp_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
);
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `datos_personales`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `datos_personales` WRITE;
/*!40000 ALTER TABLE `datos_personales` DISABLE KEYS */;
INSERT INTO `datos_personales` VALUES
(1,'ATL-2026-8047','MAML090130HMCNRNA1','LEONARDO','Manuel','Moreno','Masculino','2000-01-20',26,0,NULL,'S/N','7228540676','imc911@outlook.com',3,1,1,1,1,'2026-06-06 18:35:26','Libre');
/*!40000 ALTER TABLE `datos_personales` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `delegaciones `
--

DROP TABLE IF EXISTS `delegaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `delegaciones` (
  `id_delegacion` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `tipo` varchar(60) DEFAULT NULL,
  PRIMARY KEY (`id_delegacion`)
);/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `delegaciones `
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `delegaciones` WRITE;
/*!40000 ALTER TABLE `delegaciones` DISABLE KEYS */;
INSERT INTO `delegaciones` VALUES
(1,'Cabecera','urbana'),
(2,'San Antonio','rural'),
(3,'Zolotepec','rural'),
(4,'Zapata','rural'),
(5,'Otra',NULL);
/*!40000 ALTER TABLE `delegaciones` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `entrenador`
--

DROP TABLE IF EXISTS `entrenador`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `entrenador` (
  `id_entrenador` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `perfil` varchar(100) DEFAULT NULL,
  `anios_experiencia` tinyint(3) unsigned DEFAULT NULL,
  `num_telefonico` varchar(20) DEFAULT NULL,
  `direccion` varchar(200) DEFAULT NULL,
  `id_delegacion` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id_entrenador`),
  KEY `fk_entr_delegacion` (`id_delegacion`),
  CONSTRAINT `fk_entr_delegacion` FOREIGN KEY (`id_delegacion`) REFERENCES `delegaciones` (`id_delegacion`)
) ;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `entrenador`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `entrenador` WRITE;
/*!40000 ALTER TABLE `entrenador` DISABLE KEYS */;
/*!40000 ALTER TABLE `entrenador` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `informacion_medica`
--

DROP TABLE IF EXISTS `informacion_medica`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `informacion_medica` (
  `id_informacion_medica` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tipo_sangre` enum('A','B','AB','O') DEFAULT NULL,
  `factor_rh` enum('Positivo','Negativo') DEFAULT NULL,
  `alergias` text DEFAULT NULL,
  `padecimientos` text DEFAULT NULL,
  PRIMARY KEY (`id_informacion_medica`)
);
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `informacion_medica`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `informacion_medica` WRITE;
/*!40000 ALTER TABLE `informacion_medica` DISABLE KEYS */;
INSERT INTO `informacion_medica` VALUES
(1,'O','Negativo',NULL,NULL);
/*!40000 ALTER TABLE `informacion_medica` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `ligas`
--

DROP TABLE IF EXISTS `ligas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ligas` (
  `id_liga` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `cancha` varchar(100) DEFAULT NULL,
  `disciplina` varchar(60) DEFAULT NULL,
  `id_delegacion` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id_liga`),
  KEY `fk_liga_del` (`id_delegacion`),
  CONSTRAINT `fk_liga_del` FOREIGN KEY (`id_delegacion`) REFERENCES `delegaciones` (`id_delegacion`)
) ;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ligas`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `ligas` WRITE;
/*!40000 ALTER TABLE `ligas` DISABLE KEYS */;
/*!40000 ALTER TABLE `ligas` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `perfil_deportivo`
--

DROP TABLE IF EXISTS `perfil_deportivo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `perfil_deportivo` (
  `id_perfil` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `disciplina` varchar(60) NOT NULL,
  `nivel` varchar(30) DEFAULT NULL,
  `modalidad` varchar(30) DEFAULT NULL,
  `id_datos_personales` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id_perfil`),
  KEY `idx_perfil_datos` (`id_datos_personales`),
  CONSTRAINT `fk_perfil_datos` FOREIGN KEY (`id_datos_personales`) REFERENCES `datos_personales` (`id_datos_personales`) ON DELETE CASCADE
);
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `perfil_deportivo`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `perfil_deportivo` WRITE;
/*!40000 ALTER TABLE `perfil_deportivo` DISABLE KEYS */;
INSERT INTO `perfil_deportivo` VALUES
(1,'Voleibol','Intermedio','Equipo',1);
/*!40000 ALTER TABLE `perfil_deportivo` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `seguro`
--

DROP TABLE IF EXISTS `seguro`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seguro` (
  `id_seguro` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tipo` varchar(60) NOT NULL,
  `estado` varchar(30) DEFAULT 'activo',
  `no_afiliacion` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`id_seguro`)
);
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seguro`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `seguro` WRITE;
/*!40000 ALTER TABLE `seguro` DISABLE KEYS */;
INSERT INTO `seguro` VALUES
(1,'Ninguno','activo',NULL);
/*!40000 ALTER TABLE `seguro` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `sesiones`
--

DROP TABLE IF EXISTS `sesiones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sesiones` (
  `id_sesion` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_usuario` int(10) unsigned NOT NULL,
  `token_sesion` text NOT NULL,
  `inicio_sesion` datetime NOT NULL DEFAULT current_timestamp(),
  `expira_en` datetime NOT NULL,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_sesion`),
  KEY `idx_sesiones_usuario` (`id_usuario`),
  CONSTRAINT `fk_sesiones_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE
);
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sesiones`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `sesiones` WRITE;
/*!40000 ALTER TABLE `sesiones` DISABLE KEYS */;
INSERT INTO `sesiones` VALUES
(1,3,'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOjMsInJvbCI6ImFkbWluIiwiaWF0IjoxNzgwNzkxMjAzLCJleHAiOjE3ODA3OTQ4MDN9.lNSwoIEKKiU1F2NfON6fMaHM2keU8OPF4w70MetuVxc','2026-06-06 18:13:23','2026-06-06 19:13:23',1),
(2,3,'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOjMsInJvbCI6ImFkbWluIiwiaWF0IjoxNzgwNzkxNzM0LCJleHAiOjE3ODA3OTUzMzR9.UJqMNYkyOG4lIzNUtnPIILSaOOFAJ71LiQvVQCpdl84','2026-06-06 18:22:14','2026-06-06 19:22:14',1),
(3,3,'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOjMsInJvbCI6ImFkbWluIiwiaWF0IjoxNzgwNzkxNzQzLCJleHAiOjE3ODA3OTUzNDN9.z8knRVE2GT0rblhudchUlsJXeeFPSeshhMZ4ii-hIGc','2026-06-06 18:22:23','2026-06-06 19:22:23',1),
(4,3,'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOjMsInJvbCI6ImFkbWluIiwiaWF0IjoxNzgwNzkyNDUxLCJleHAiOjE3ODA3OTYwNTF9.XtHCRLbujmv4BqS0EQBguiq3NKVJ2jsoi-e7Q-h_oh8','2026-06-06 18:34:11','2026-06-06 19:34:11',1),
(5,3,'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOjMsInJvbCI6ImFkbWluIiwiaWF0IjoxNzgwNzkyNDY0LCJleHAiOjE3ODA3OTYwNjR9.ZXnEoK5Ixr7vnraDlI0T8_G2k3o7iJSuyYvenfKw5c4','2026-06-06 18:34:24','2026-06-06 19:34:24',1),
(6,3,'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOjMsInJvbCI6ImFkbWluIiwiaWF0IjoxNzgwNzkzNDAyLCJleHAiOjE3ODA3OTcwMDJ9.hFmHbveXsfhf0qVtMFrHciHkqiHsw1qQcy9UBlQnx3c','2026-06-06 18:50:02','2026-06-06 19:50:02',1),
(7,3,'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOjMsInJvbCI6ImFkbWluIiwiaWF0IjoxNzgwNzkzOTAwLCJleHAiOjE3ODA3OTc1MDB9.NZe1cdJ4oZcqOYITmfZvR18LlvS0iDfNabfDzr_cqao','2026-06-06 18:58:20','2026-06-06 19:58:20',1),
(8,3,'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOjMsInJvbCI6ImFkbWluIiwiaWF0IjoxNzgwNzkzOTU4LCJleHAiOjE3ODA3OTc1NTh9.V6E7WabeVempyNacwl1mO6sHp5MFwSKGDxvtRFNyDTI','2026-06-06 18:59:18','2026-06-06 19:59:18',1),
(9,3,'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOjMsInJvbCI6ImFkbWluIiwiaWF0IjoxNzgwNzk0MzQ2LCJleHAiOjE3ODA3OTc5NDZ9.PlS0WctCRgg43-NddnpaTT6KcVK_LciraoaF83EUlw8','2026-06-06 19:05:46','2026-06-06 20:05:46',1),
(10,3,'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOjMsInJvbCI6ImFkbWluIiwiaWF0IjoxNzgwNzk1MzkwLCJleHAiOjE3ODA3OTg5OTB9.mmnMw83b8Af_w-53_lOHAOTYWWHw2DXQXB0oWzAzfQM','2026-06-06 19:23:10','2026-06-06 20:23:10',1),
(11,3,'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOjMsInJvbCI6ImFkbWluIiwiaWF0IjoxNzgwNzk1NjY5LCJleHAiOjE3ODA3OTkyNjl9.-A7_EVzr-1CWWIfmWzIITe6NdAqNo0KZ9zVEthy4Vfc','2026-06-06 19:27:49','2026-06-06 20:27:49',1);
/*!40000 ALTER TABLE `sesiones` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `talleres`
--

DROP TABLE IF EXISTS `talleres`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `talleres` (
  `id_taller` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `horario` varchar(100) DEFAULT NULL,
  `tipo` varchar(60) DEFAULT NULL,
  `id_entrenador` int(10) unsigned DEFAULT NULL,
  `id_delegacion` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id_taller`),
  KEY `fk_taller_entr` (`id_entrenador`),
  KEY `fk_taller_del` (`id_delegacion`),
  CONSTRAINT `fk_taller_del` FOREIGN KEY (`id_delegacion`) REFERENCES `delegaciones` (`id_delegacion`),
  CONSTRAINT `fk_taller_entr` FOREIGN KEY (`id_entrenador`) REFERENCES `entrenador` (`id_entrenador`)
) ;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `talleres`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `talleres` WRITE;
/*!40000 ALTER TABLE `talleres` DISABLE KEYS */;
/*!40000 ALTER TABLE `talleres` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `usuario_liga`
--

DROP TABLE IF EXISTS `usuario_liga`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuario_liga` (
  `id_usuario` int(10) unsigned NOT NULL,
  `id_liga` int(10) unsigned NOT NULL,
  `id_categoria` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id_usuario`,`id_liga`),
  KEY `fk_ul_liga` (`id_liga`),
  KEY `fk_ul_categoria` (`id_categoria`),
  CONSTRAINT `fk_ul_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categoria` (`id_categoria`),
  CONSTRAINT `fk_ul_liga` FOREIGN KEY (`id_liga`) REFERENCES `ligas` (`id_liga`) ON DELETE CASCADE,
  CONSTRAINT `fk_ul_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE
) ;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuario_liga`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `usuario_liga` WRITE;
/*!40000 ALTER TABLE `usuario_liga` DISABLE KEYS */;
/*!40000 ALTER TABLE `usuario_liga` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `usuario_taller`
--

DROP TABLE IF EXISTS `usuario_taller`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuario_taller` (
  `id_usuario` int(10) unsigned NOT NULL,
  `id_taller` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id_usuario`,`id_taller`),
  KEY `fk_ut_taller` (`id_taller`),
  CONSTRAINT `fk_ut_taller` FOREIGN KEY (`id_taller`) REFERENCES `talleres` (`id_taller`) ON DELETE CASCADE,
  CONSTRAINT `fk_ut_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE
) ;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuario_taller`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `usuario_taller` WRITE;
/*!40000 ALTER TABLE `usuario_taller` DISABLE KEYS */;
/*!40000 ALTER TABLE `usuario_taller` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id_usuario` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(180) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `rol` enum('admin','entrenador','atleta','visitante') NOT NULL DEFAULT 'atleta',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `intentos_fallidos` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `bloqueado_hasta` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `uq_email` (`email`)
);
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES
(1,'admin@deporte.mx','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','admin',1,4,NULL,'2026-06-06 17:26:10'),
(3,'admin@depo.mx','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','admin',1,0,NULL,'2026-06-06 18:05:14');
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-06-11 14:46:29
