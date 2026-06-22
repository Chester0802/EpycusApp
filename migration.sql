CREATE TABLE IF NOT EXISTS `__EFMigrationsHistory` (
    `MigrationId` varchar(150) CHARACTER SET utf8mb4 NOT NULL,
    `ProductVersion` varchar(32) CHARACTER SET utf8mb4 NOT NULL,
    CONSTRAINT `PK___EFMigrationsHistory` PRIMARY KEY (`MigrationId`)
) CHARACTER SET=utf8mb4;

START TRANSACTION;
DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    ALTER DATABASE CHARACTER SET utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE TABLE `Carreras` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `Nombre` longtext CHARACTER SET utf8mb4 NOT NULL,
        `Area` longtext CHARACTER SET utf8mb4 NOT NULL,
        `Codigo` longtext CHARACTER SET utf8mb4 NOT NULL,
        `EstaActiva` tinyint(1) NOT NULL,
        CONSTRAINT `PK_Carreras` PRIMARY KEY (`Id`)
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE TABLE `Categorias` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `Nombre` longtext CHARACTER SET utf8mb4 NOT NULL,
        `Icono` longtext CHARACTER SET utf8mb4 NOT NULL,
        `Tipo` longtext CHARACTER SET utf8mb4 NOT NULL,
        `EstaActiva` tinyint(1) NOT NULL,
        CONSTRAINT `PK_Categorias` PRIMARY KEY (`Id`)
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE TABLE `FrasesMotivacionales` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `Frase` longtext CHARACTER SET utf8mb4 NOT NULL,
        `Autor` longtext CHARACTER SET utf8mb4 NOT NULL,
        `EstaActiva` tinyint(1) NOT NULL,
        CONSTRAINT `PK_FrasesMotivacionales` PRIMARY KEY (`Id`)
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE TABLE `Logros` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `Nombre` longtext CHARACTER SET utf8mb4 NOT NULL,
        `Descripcion` longtext CHARACTER SET utf8mb4 NOT NULL,
        `IconoUrl` longtext CHARACTER SET utf8mb4 NOT NULL,
        `CondicionTipo` longtext CHARACTER SET utf8mb4 NOT NULL,
        `CondicionValor` int NOT NULL,
        `XpRecompensa` int NOT NULL,
        `EstaActivo` tinyint(1) NOT NULL,
        CONSTRAINT `PK_Logros` PRIMARY KEY (`Id`)
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE TABLE `Niveles` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `Numero` int NOT NULL,
        `Titulo` longtext CHARACTER SET utf8mb4 NOT NULL,
        `XpRequerido` int NOT NULL,
        `Descripcion` longtext CHARACTER SET utf8mb4 NULL,
        CONSTRAINT `PK_Niveles` PRIMARY KEY (`Id`)
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE TABLE `Roles` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `Nombre` longtext CHARACTER SET utf8mb4 NOT NULL,
        CONSTRAINT `PK_Roles` PRIMARY KEY (`Id`)
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE TABLE `Temas` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `Nombre` longtext CHARACTER SET utf8mb4 NOT NULL,
        `Descripcion` longtext CHARACTER SET utf8mb4 NULL,
        `Modo` longtext CHARACTER SET utf8mb4 NOT NULL,
        `ArchivoCss` longtext CHARACTER SET utf8mb4 NOT NULL,
        `ImagenPreviewUrl` longtext CHARACTER SET utf8mb4 NULL,
        `EsPremium` tinyint(1) NOT NULL,
        `Precio` decimal(65,30) NOT NULL,
        `EstaActivo` tinyint(1) NOT NULL,
        CONSTRAINT `PK_Temas` PRIMARY KEY (`Id`)
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE TABLE `TipsPomodoro` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `Tip` longtext CHARACTER SET utf8mb4 NOT NULL,
        `EstaActivo` tinyint(1) NOT NULL,
        CONSTRAINT `PK_TipsPomodoro` PRIMARY KEY (`Id`)
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE TABLE `Personajes` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `Nombre` longtext CHARACTER SET utf8mb4 NOT NULL,
        `Genero` longtext CHARACTER SET utf8mb4 NOT NULL,
        `EstaActivo` tinyint(1) NOT NULL,
        `CarreraId` int NULL,
        CONSTRAINT `PK_Personajes` PRIMARY KEY (`Id`),
        CONSTRAINT `FK_Personajes_Carreras_CarreraId` FOREIGN KEY (`CarreraId`) REFERENCES `Carreras` (`Id`) ON DELETE SET NULL
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE TABLE `Usuarios` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `CodigoUnico` varchar(255) CHARACTER SET utf8mb4 NOT NULL,
        `Nombre` longtext CHARACTER SET utf8mb4 NOT NULL,
        `CorreoElectronico` varchar(255) CHARACTER SET utf8mb4 NOT NULL,
        `ContrasenaHash` longtext CHARACTER SET utf8mb4 NULL,
        `FechaNacimiento` date NOT NULL,
        `Genero` longtext CHARACTER SET utf8mb4 NOT NULL,
        `CorreoVerificado` tinyint(1) NOT NULL,
        `AceptoTerminos` tinyint(1) NOT NULL,
        `EstaActivo` tinyint(1) NOT NULL,
        `FechaRegistro` datetime(6) NOT NULL,
        `UltimoAcceso` datetime(6) NULL,
        `GoogleId` varchar(255) CHARACTER SET utf8mb4 NULL,
        `FotoGoogleUrl` longtext CHARACTER SET utf8mb4 NULL,
        `RolId` int NOT NULL,
        `CarreraId` int NOT NULL,
        `TemaActualId` int NULL,
        CONSTRAINT `PK_Usuarios` PRIMARY KEY (`Id`),
        CONSTRAINT `FK_Usuarios_Carreras_CarreraId` FOREIGN KEY (`CarreraId`) REFERENCES `Carreras` (`Id`) ON DELETE RESTRICT,
        CONSTRAINT `FK_Usuarios_Roles_RolId` FOREIGN KEY (`RolId`) REFERENCES `Roles` (`Id`) ON DELETE RESTRICT,
        CONSTRAINT `FK_Usuarios_Temas_TemaActualId` FOREIGN KEY (`TemaActualId`) REFERENCES `Temas` (`Id`) ON DELETE SET NULL
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE TABLE `ImagenesNivelPersonaje` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `NivelNumero` int NOT NULL,
        `ImagenUrl` longtext CHARACTER SET utf8mb4 NOT NULL,
        `EsPlaceholder` tinyint(1) NOT NULL,
        `PersonajeId` int NOT NULL,
        CONSTRAINT `PK_ImagenesNivelPersonaje` PRIMARY KEY (`Id`),
        CONSTRAINT `FK_ImagenesNivelPersonaje_Personajes_PersonajeId` FOREIGN KEY (`PersonajeId`) REFERENCES `Personajes` (`Id`) ON DELETE CASCADE
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE TABLE `ConfiguracionesPomodoro` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `TiempoEstudioMin` int NOT NULL,
        `TiempoDescansoMin` int NOT NULL,
        `TiempoDescansoLargoMin` int NOT NULL,
        `CiclosAntesDescansoLargo` int NOT NULL,
        `SonidoActivo` tinyint(1) NOT NULL,
        `FechaActualizacion` datetime(6) NOT NULL,
        `UsuarioId` int NOT NULL,
        CONSTRAINT `PK_ConfiguracionesPomodoro` PRIMARY KEY (`Id`),
        CONSTRAINT `FK_ConfiguracionesPomodoro_Usuarios_UsuarioId` FOREIGN KEY (`UsuarioId`) REFERENCES `Usuarios` (`Id`) ON DELETE CASCADE
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE TABLE `EstadosAnimo` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `Fecha` date NOT NULL,
        `Estado` longtext CHARACTER SET utf8mb4 NOT NULL,
        `Nota` longtext CHARACTER SET utf8mb4 NULL,
        `FechaRegistro` datetime(6) NOT NULL,
        `UsuarioId` int NOT NULL,
        CONSTRAINT `PK_EstadosAnimo` PRIMARY KEY (`Id`),
        CONSTRAINT `FK_EstadosAnimo_Usuarios_UsuarioId` FOREIGN KEY (`UsuarioId`) REFERENCES `Usuarios` (`Id`) ON DELETE CASCADE
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE TABLE `Habitos` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `Nombre` longtext CHARACTER SET utf8mb4 NOT NULL,
        `Descripcion` longtext CHARACTER SET utf8mb4 NULL,
        `Frecuencia` longtext CHARACTER SET utf8mb4 NOT NULL,
        `ConPomodoro` tinyint(1) NOT NULL,
        `RecordatorioHora` time(6) NULL,
        `RachaActual` int NOT NULL,
        `RachaMaxima` int NOT NULL,
        `EstaActivo` tinyint(1) NOT NULL,
        `FechaCreacion` datetime(6) NOT NULL,
        `UsuarioId` int NOT NULL,
        `CategoriaId` int NOT NULL,
        CONSTRAINT `PK_Habitos` PRIMARY KEY (`Id`),
        CONSTRAINT `FK_Habitos_Categorias_CategoriaId` FOREIGN KEY (`CategoriaId`) REFERENCES `Categorias` (`Id`) ON DELETE RESTRICT,
        CONSTRAINT `FK_Habitos_Usuarios_UsuarioId` FOREIGN KEY (`UsuarioId`) REFERENCES `Usuarios` (`Id`) ON DELETE CASCADE
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE TABLE `Log` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `Accion` longtext CHARACTER SET utf8mb4 NOT NULL,
        `Detalle` longtext CHARACTER SET utf8mb4 NULL,
        `DireccionIp` longtext CHARACTER SET utf8mb4 NULL,
        `FechaRegistro` datetime(6) NOT NULL,
        `UsuarioId` int NULL,
        CONSTRAINT `PK_Log` PRIMARY KEY (`Id`),
        CONSTRAINT `FK_Log_Usuarios_UsuarioId` FOREIGN KEY (`UsuarioId`) REFERENCES `Usuarios` (`Id`)
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE TABLE `LogrosUsuario` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `FechaObtenido` datetime(6) NOT NULL,
        `UsuarioId` int NOT NULL,
        `LogroId` int NOT NULL,
        CONSTRAINT `PK_LogrosUsuario` PRIMARY KEY (`Id`),
        CONSTRAINT `FK_LogrosUsuario_Logros_LogroId` FOREIGN KEY (`LogroId`) REFERENCES `Logros` (`Id`) ON DELETE CASCADE,
        CONSTRAINT `FK_LogrosUsuario_Usuarios_UsuarioId` FOREIGN KEY (`UsuarioId`) REFERENCES `Usuarios` (`Id`) ON DELETE CASCADE
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE TABLE `MensajesIA` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `ConversacionId` varchar(255) CHARACTER SET utf8mb4 NOT NULL,
        `UsuarioId` int NOT NULL,
        `Rol` longtext CHARACTER SET utf8mb4 NOT NULL,
        `Contenido` longtext CHARACTER SET utf8mb4 NOT NULL,
        `FechaHora` datetime(6) NOT NULL,
        CONSTRAINT `PK_MensajesIA` PRIMARY KEY (`Id`),
        CONSTRAINT `FK_MensajesIA_Usuarios_UsuarioId` FOREIGN KEY (`UsuarioId`) REFERENCES `Usuarios` (`Id`) ON DELETE CASCADE
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE TABLE `Misiones` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `Nombre` longtext CHARACTER SET utf8mb4 NOT NULL,
        `Descripcion` longtext CHARACTER SET utf8mb4 NULL,
        `NombreCurso` longtext CHARACTER SET utf8mb4 NULL,
        `FechaLimite` date NOT NULL,
        `Prioridad` longtext CHARACTER SET utf8mb4 NOT NULL,
        `Estado` longtext CHARACTER SET utf8mb4 NOT NULL,
        `ConPomodoro` tinyint(1) NOT NULL,
        `XpOtorgado` int NOT NULL,
        `FechaCreacion` datetime(6) NOT NULL,
        `FechaCompletado` datetime(6) NULL,
        `UsuarioId` int NOT NULL,
        `CategoriaId` int NOT NULL,
        CONSTRAINT `PK_Misiones` PRIMARY KEY (`Id`),
        CONSTRAINT `FK_Misiones_Categorias_CategoriaId` FOREIGN KEY (`CategoriaId`) REFERENCES `Categorias` (`Id`) ON DELETE RESTRICT,
        CONSTRAINT `FK_Misiones_Usuarios_UsuarioId` FOREIGN KEY (`UsuarioId`) REFERENCES `Usuarios` (`Id`) ON DELETE CASCADE
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE TABLE `PersonajesUsuario` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `EstaSeleccionado` tinyint(1) NOT NULL,
        `FechaObtenido` datetime(6) NOT NULL,
        `UsuarioId` int NOT NULL,
        `PersonajeId` int NOT NULL,
        CONSTRAINT `PK_PersonajesUsuario` PRIMARY KEY (`Id`),
        CONSTRAINT `FK_PersonajesUsuario_Personajes_PersonajeId` FOREIGN KEY (`PersonajeId`) REFERENCES `Personajes` (`Id`) ON DELETE CASCADE,
        CONSTRAINT `FK_PersonajesUsuario_Usuarios_UsuarioId` FOREIGN KEY (`UsuarioId`) REFERENCES `Usuarios` (`Id`) ON DELETE CASCADE
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE TABLE `ProgresosUsuario` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `XpTotal` int NOT NULL,
        `RachaActual` int NOT NULL,
        `RachaMaxima` int NOT NULL,
        `FechaUltimaActividad` datetime(6) NULL,
        `FechaInicioRacha` datetime(6) NULL,
        `DiaDeGraciaUsado` tinyint(1) NOT NULL,
        `ProductividadDiaria` decimal(65,30) NOT NULL,
        `UsuarioId` int NOT NULL,
        `NivelActualId` int NOT NULL,
        CONSTRAINT `PK_ProgresosUsuario` PRIMARY KEY (`Id`),
        CONSTRAINT `FK_ProgresosUsuario_Niveles_NivelActualId` FOREIGN KEY (`NivelActualId`) REFERENCES `Niveles` (`Id`) ON DELETE RESTRICT,
        CONSTRAINT `FK_ProgresosUsuario_Usuarios_UsuarioId` FOREIGN KEY (`UsuarioId`) REFERENCES `Usuarios` (`Id`) ON DELETE CASCADE
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE TABLE `RecuperacionesContrasena` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `Token` longtext CHARACTER SET utf8mb4 NOT NULL,
        `ExpiraEn` datetime(6) NOT NULL,
        `Usado` tinyint(1) NOT NULL,
        `FechaCreacion` datetime(6) NOT NULL,
        `UsuarioId` int NOT NULL,
        CONSTRAINT `PK_RecuperacionesContrasena` PRIMARY KEY (`Id`),
        CONSTRAINT `FK_RecuperacionesContrasena_Usuarios_UsuarioId` FOREIGN KEY (`UsuarioId`) REFERENCES `Usuarios` (`Id`) ON DELETE CASCADE
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE TABLE `Suscripciones` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `Plan` longtext CHARACTER SET utf8mb4 NOT NULL,
        `PrecioSoles` decimal(65,30) NOT NULL,
        `FechaInicio` date NOT NULL,
        `FechaFin` date NOT NULL,
        `EstaActiva` tinyint(1) NOT NULL,
        `ActivadaPorAdminId` int NULL,
        `FechaActivacion` datetime(6) NULL,
        `UsuarioId` int NOT NULL,
        CONSTRAINT `PK_Suscripciones` PRIMARY KEY (`Id`),
        CONSTRAINT `FK_Suscripciones_Usuarios_ActivadaPorAdminId` FOREIGN KEY (`ActivadaPorAdminId`) REFERENCES `Usuarios` (`Id`) ON DELETE SET NULL,
        CONSTRAINT `FK_Suscripciones_Usuarios_UsuarioId` FOREIGN KEY (`UsuarioId`) REFERENCES `Usuarios` (`Id`) ON DELETE CASCADE
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE TABLE `TemasUsuario` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `FechaObtenido` datetime(6) NOT NULL,
        `UsuarioId` int NOT NULL,
        `TemaId` int NOT NULL,
        CONSTRAINT `PK_TemasUsuario` PRIMARY KEY (`Id`),
        CONSTRAINT `FK_TemasUsuario_Temas_TemaId` FOREIGN KEY (`TemaId`) REFERENCES `Temas` (`Id`) ON DELETE CASCADE,
        CONSTRAINT `FK_TemasUsuario_Usuarios_UsuarioId` FOREIGN KEY (`UsuarioId`) REFERENCES `Usuarios` (`Id`) ON DELETE CASCADE
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE TABLE `TokensRefresh` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `Token` varchar(255) CHARACTER SET utf8mb4 NOT NULL,
        `ExpiraEn` datetime(6) NOT NULL,
        `Revocado` tinyint(1) NOT NULL,
        `FechaCreacion` datetime(6) NOT NULL,
        `UsuarioId` int NOT NULL,
        CONSTRAINT `PK_TokensRefresh` PRIMARY KEY (`Id`),
        CONSTRAINT `FK_TokensRefresh_Usuarios_UsuarioId` FOREIGN KEY (`UsuarioId`) REFERENCES `Usuarios` (`Id`) ON DELETE CASCADE
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE TABLE `VerificacionesCorreo` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `Token` longtext CHARACTER SET utf8mb4 NOT NULL,
        `ExpiraEn` datetime(6) NOT NULL,
        `Usado` tinyint(1) NOT NULL,
        `FechaCreacion` datetime(6) NOT NULL,
        `UsuarioId` int NOT NULL,
        CONSTRAINT `PK_VerificacionesCorreo` PRIMARY KEY (`Id`),
        CONSTRAINT `FK_VerificacionesCorreo_Usuarios_UsuarioId` FOREIGN KEY (`UsuarioId`) REFERENCES `Usuarios` (`Id`) ON DELETE CASCADE
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE TABLE `DiasSemanaHabito` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `HabitoId` int NOT NULL,
        `DiaSemana` int NOT NULL,
        CONSTRAINT `PK_DiasSemanaHabito` PRIMARY KEY (`Id`),
        CONSTRAINT `FK_DiasSemanaHabito_Habitos_HabitoId` FOREIGN KEY (`HabitoId`) REFERENCES `Habitos` (`Id`) ON DELETE CASCADE
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE TABLE `RegistrosHabito` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `Fecha` date NOT NULL,
        `Estado` longtext CHARACTER SET utf8mb4 NOT NULL,
        `XpOtorgado` int NOT NULL,
        `FechaRegistro` datetime(6) NOT NULL,
        `HabitoId` int NOT NULL,
        CONSTRAINT `PK_RegistrosHabito` PRIMARY KEY (`Id`),
        CONSTRAINT `FK_RegistrosHabito_Habitos_HabitoId` FOREIGN KEY (`HabitoId`) REFERENCES `Habitos` (`Id`) ON DELETE CASCADE
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE TABLE `SesionesPomodoro` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `FechaInicio` datetime(6) NOT NULL,
        `FechaFin` datetime(6) NULL,
        `CiclosCompletados` int NOT NULL,
        `XpOtorgado` int NOT NULL,
        `FueCompletada` tinyint(1) NOT NULL,
        `UsuarioId` int NOT NULL,
        `HabitoId` int NULL,
        `MisionId` int NULL,
        CONSTRAINT `PK_SesionesPomodoro` PRIMARY KEY (`Id`),
        CONSTRAINT `FK_SesionesPomodoro_Habitos_HabitoId` FOREIGN KEY (`HabitoId`) REFERENCES `Habitos` (`Id`) ON DELETE SET NULL,
        CONSTRAINT `FK_SesionesPomodoro_Misiones_MisionId` FOREIGN KEY (`MisionId`) REFERENCES `Misiones` (`Id`) ON DELETE SET NULL,
        CONSTRAINT `FK_SesionesPomodoro_Usuarios_UsuarioId` FOREIGN KEY (`UsuarioId`) REFERENCES `Usuarios` (`Id`) ON DELETE CASCADE
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE UNIQUE INDEX `IX_ConfiguracionesPomodoro_UsuarioId` ON `ConfiguracionesPomodoro` (`UsuarioId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE INDEX `IX_DiasSemanaHabito_HabitoId` ON `DiasSemanaHabito` (`HabitoId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE INDEX `IX_EstadosAnimo_UsuarioId` ON `EstadosAnimo` (`UsuarioId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE INDEX `IX_Habitos_CategoriaId` ON `Habitos` (`CategoriaId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE INDEX `IX_Habitos_UsuarioId` ON `Habitos` (`UsuarioId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE INDEX `IX_ImagenesNivelPersonaje_PersonajeId` ON `ImagenesNivelPersonaje` (`PersonajeId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE INDEX `IX_Log_UsuarioId` ON `Log` (`UsuarioId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE INDEX `IX_LogrosUsuario_LogroId` ON `LogrosUsuario` (`LogroId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE INDEX `IX_LogrosUsuario_UsuarioId` ON `LogrosUsuario` (`UsuarioId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE INDEX `IX_MensajesIA_ConversacionId` ON `MensajesIA` (`ConversacionId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE INDEX `IX_MensajesIA_UsuarioId` ON `MensajesIA` (`UsuarioId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE INDEX `IX_Misiones_CategoriaId` ON `Misiones` (`CategoriaId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE INDEX `IX_Misiones_UsuarioId` ON `Misiones` (`UsuarioId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE INDEX `IX_Personajes_CarreraId` ON `Personajes` (`CarreraId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE INDEX `IX_PersonajesUsuario_PersonajeId` ON `PersonajesUsuario` (`PersonajeId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE INDEX `IX_PersonajesUsuario_UsuarioId` ON `PersonajesUsuario` (`UsuarioId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE INDEX `IX_ProgresosUsuario_NivelActualId` ON `ProgresosUsuario` (`NivelActualId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE UNIQUE INDEX `IX_ProgresosUsuario_UsuarioId` ON `ProgresosUsuario` (`UsuarioId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE INDEX `IX_RecuperacionesContrasena_UsuarioId` ON `RecuperacionesContrasena` (`UsuarioId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE INDEX `IX_RegistrosHabito_HabitoId` ON `RegistrosHabito` (`HabitoId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE INDEX `IX_SesionesPomodoro_HabitoId` ON `SesionesPomodoro` (`HabitoId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE INDEX `IX_SesionesPomodoro_MisionId` ON `SesionesPomodoro` (`MisionId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE INDEX `IX_SesionesPomodoro_UsuarioId` ON `SesionesPomodoro` (`UsuarioId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE INDEX `IX_Suscripciones_ActivadaPorAdminId` ON `Suscripciones` (`ActivadaPorAdminId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE INDEX `IX_Suscripciones_UsuarioId` ON `Suscripciones` (`UsuarioId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE INDEX `IX_TemasUsuario_TemaId` ON `TemasUsuario` (`TemaId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE INDEX `IX_TemasUsuario_UsuarioId` ON `TemasUsuario` (`UsuarioId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE INDEX `IX_TokensRefresh_Token` ON `TokensRefresh` (`Token`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE INDEX `IX_TokensRefresh_UsuarioId` ON `TokensRefresh` (`UsuarioId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE INDEX `IX_Usuarios_CarreraId` ON `Usuarios` (`CarreraId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE UNIQUE INDEX `IX_Usuarios_CodigoUnico` ON `Usuarios` (`CodigoUnico`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE UNIQUE INDEX `IX_Usuarios_CorreoElectronico` ON `Usuarios` (`CorreoElectronico`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE UNIQUE INDEX `IX_Usuarios_GoogleId` ON `Usuarios` (`GoogleId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE INDEX `IX_Usuarios_RolId` ON `Usuarios` (`RolId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE INDEX `IX_Usuarios_TemaActualId` ON `Usuarios` (`TemaActualId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    CREATE INDEX `IX_VerificacionesCorreo_UsuarioId` ON `VerificacionesCorreo` (`UsuarioId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260615234414_Initial') THEN

    INSERT INTO `__EFMigrationsHistory` (`MigrationId`, `ProductVersion`)
    VALUES ('20260615234414_Initial', '9.0.0');

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260618182406_AddCategoriaToFraseMotivacional') THEN

    INSERT INTO `__EFMigrationsHistory` (`MigrationId`, `ProductVersion`)
    VALUES ('20260618182406_AddCategoriaToFraseMotivacional', '9.0.0');

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260618223131_AddFeedbackToMensajesIA') THEN

    INSERT INTO `__EFMigrationsHistory` (`MigrationId`, `ProductVersion`)
    VALUES ('20260618223131_AddFeedbackToMensajesIA', '9.0.0');

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260619000237_AddEntradaDiario') THEN

    CREATE TABLE `EntradasDiario` (
        `Id` int NOT NULL AUTO_INCREMENT,
        `Fecha` date NOT NULL,
        `EstadoAnimo` int NOT NULL,
        `NivelEnergia` int NOT NULL,
        `HorasSueno` decimal(65,30) NULL,
        `NivelEstres` int NULL,
        `ActividadFisica` tinyint(1) NULL,
        `DiarioTexto` longtext CHARACTER SET utf8mb4 NULL,
        `PreguntaGuia` longtext CHARACTER SET utf8mb4 NULL,
        `RespuestaGuia` longtext CHARACTER SET utf8mb4 NULL,
        `FechaRegistro` datetime(6) NOT NULL,
        `UsuarioId` int NOT NULL,
        CONSTRAINT `PK_EntradasDiario` PRIMARY KEY (`Id`),
        CONSTRAINT `FK_EntradasDiario_Usuarios_UsuarioId` FOREIGN KEY (`UsuarioId`) REFERENCES `Usuarios` (`Id`) ON DELETE CASCADE
    ) CHARACTER SET=utf8mb4;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260619000237_AddEntradaDiario') THEN

    CREATE INDEX `IX_EntradasDiario_UsuarioId` ON `EntradasDiario` (`UsuarioId`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260619000237_AddEntradaDiario') THEN

    CREATE UNIQUE INDEX `IX_EntradasDiario_UsuarioId_Fecha` ON `EntradasDiario` (`UsuarioId`, `Fecha`);

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260619000237_AddEntradaDiario') THEN

    INSERT INTO `__EFMigrationsHistory` (`MigrationId`, `ProductVersion`)
    VALUES ('20260619000237_AddEntradaDiario', '9.0.0');

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260621163455_AddPomodoroConfigExtras') THEN

    ALTER TABLE `ConfiguracionesPomodoro` ADD `AutoIniciarDescanso` tinyint(1) NOT NULL DEFAULT FALSE;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260621163455_AddPomodoroConfigExtras') THEN

    ALTER TABLE `ConfiguracionesPomodoro` ADD `AutoIniciarEnfoque` tinyint(1) NOT NULL DEFAULT FALSE;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260621163455_AddPomodoroConfigExtras') THEN

    ALTER TABLE `ConfiguracionesPomodoro` ADD `MetaDiariaCiclos` int NOT NULL DEFAULT 0;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260621163455_AddPomodoroConfigExtras') THEN

    ALTER TABLE `ConfiguracionesPomodoro` ADD `ModoPersonalizadoMinutos` int NOT NULL DEFAULT 25;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260621163455_AddPomodoroConfigExtras') THEN

    ALTER TABLE `ConfiguracionesPomodoro` ADD `NotificacionDesktop` tinyint(1) NOT NULL DEFAULT TRUE;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260621163455_AddPomodoroConfigExtras') THEN

    ALTER TABLE `ConfiguracionesPomodoro` ADD `SonidoSeleccionado` longtext CHARACTER SET utf8mb4 NOT NULL;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260621163455_AddPomodoroConfigExtras') THEN

    ALTER TABLE `ConfiguracionesPomodoro` ADD `TicTacActivo` tinyint(1) NOT NULL DEFAULT FALSE;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260621163455_AddPomodoroConfigExtras') THEN

    ALTER TABLE `ConfiguracionesPomodoro` ADD `VibracionActiva` tinyint(1) NOT NULL DEFAULT TRUE;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260621163455_AddPomodoroConfigExtras') THEN

    ALTER TABLE `ConfiguracionesPomodoro` ADD `Volumen` int NOT NULL DEFAULT 100;

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260621163455_AddPomodoroConfigExtras') THEN

    INSERT INTO `__EFMigrationsHistory` (`MigrationId`, `ProductVersion`)
    VALUES ('20260621163455_AddPomodoroConfigExtras', '9.0.0');

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260622205730_AddTipoToSesionPomodoro') THEN

    ALTER TABLE `SesionesPomodoro` ADD `Tipo` longtext CHARACTER SET utf8mb4 NOT NULL DEFAULT ('Enfoque');

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

DROP PROCEDURE IF EXISTS MigrationsScript;
DELIMITER //
CREATE PROCEDURE MigrationsScript()
BEGIN
    IF NOT EXISTS(SELECT 1 FROM `__EFMigrationsHistory` WHERE `MigrationId` = '20260622205730_AddTipoToSesionPomodoro') THEN

    INSERT INTO `__EFMigrationsHistory` (`MigrationId`, `ProductVersion`)
    VALUES ('20260622205730_AddTipoToSesionPomodoro', '9.0.0');

    END IF;
END //
DELIMITER ;
CALL MigrationsScript();
DROP PROCEDURE MigrationsScript;

COMMIT;

