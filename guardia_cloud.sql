-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 27-02-2026 a las 06:46:46
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `guardia_cloud`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `app_service`
--

CREATE TABLE `app_service` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nameService` varchar(255) NOT NULL,
  `descriptionService` text DEFAULT NULL,
  `activo` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `app_service`
--

INSERT INTO `app_service` (`id`, `nameService`, `descriptionService`, `activo`, `created_at`, `updated_at`) VALUES
(1, 'VEEAM MTY', NULL, 1, '2025-11-24 16:31:28', '2025-11-24 16:32:06'),
(2, 'VM-BR-VEEAM', NULL, 1, '2025-11-24 16:31:55', '2025-11-24 16:31:55'),
(3, 'VEEAM12-QRO', NULL, 1, '2025-11-24 16:32:36', '2025-12-27 01:52:28'),
(5, 'FIREWALL', 'PRUEBA LISTA', 1, '2025-12-29 18:11:28', '2026-01-15 06:00:52');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `areas`
--

CREATE TABLE `areas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `areas`
--

INSERT INTO `areas` (`id`, `name`, `activo`, `created_at`, `updated_at`) VALUES
(1, 'Operaciones Stratosphere', 1, '2025-12-18 18:16:27', '2025-12-18 23:56:18'),
(2, 'Infraestructura Stratosphere', 1, '2025-12-19 00:52:15', '2025-12-19 00:52:15'),
(4, 'Comunicaciones Team', 1, '2025-12-19 18:14:08', '2025-12-19 18:14:08');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `c_veeam`
--

CREATE TABLE `c_veeam` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `numCV` varchar(255) NOT NULL,
  `nameCV` varchar(255) NOT NULL,
  `app` int(11) NOT NULL,
  `backup` varchar(11) NOT NULL,
  `jobs` varchar(255) NOT NULL,
  `activo` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `c_veeam`
--

INSERT INTO `c_veeam` (`id`, `numCV`, `nameCV`, `app`, `backup`, `jobs`, `activo`, `created_at`, `updated_at`) VALUES
(1, '1687', 'Ayuntamiento de Tijuana', 1, '50.00 TB', '0', 1, '2025-11-24 18:43:36', '2025-12-23 05:19:53'),
(2, '1685', 'LosifraSADEC', 1, '10.00 TB', '8', 1, '2025-11-24 18:43:36', '2025-11-24 18:43:36'),
(3, '1566', 'mccollect', 1, '6.00 TB', '2', 1, '2025-11-24 18:43:36', '2025-11-24 18:43:36'),
(4, '1650', 'redaitpro', 1, '6.00 TB', '0', 1, '2025-11-24 18:43:36', '2025-11-24 18:43:36'),
(5, '452', 'Fulltech', 1, '5.00 TB', '3', 1, '2025-11-24 18:43:36', '2025-11-24 18:43:36'),
(6, '491', 'Unity', 1, '5.00 TB', '9', 1, '2025-11-24 18:43:36', '2025-11-24 18:43:36'),
(7, '1651', 'IDT', 1, '4.00 TB', '0', 1, '2025-11-24 18:43:36', '2025-11-24 18:43:36'),
(8, '1598', 'WorIng', 1, '3.00 TB', '2', 1, '2025-11-24 18:43:36', '2025-11-24 18:43:36'),
(9, '620', 'INTRAVERACRUZ', 1, '3.00 TB', '4', 1, '2025-11-24 18:43:36', '2025-11-24 18:43:36'),
(10, '333', 'Trainex', 1, '2.93 TB', '5', 1, '2025-11-24 18:43:36', '2025-11-24 18:43:36'),
(11, 'INTERNO', 'dmarcos', 2, '2.44 TB', '0', 1, '2025-11-24 18:43:36', '2025-11-24 18:43:36'),
(12, '1655', 'ECOSA', 1, '2.44 TB', '2', 1, '2025-11-24 18:43:36', '2025-11-24 18:43:36'),
(13, '1672', 'datavision', 1, '2.25 TB', '0', 1, '2025-11-24 18:43:36', '2025-11-24 18:43:36'),
(14, '172', 'ARTYEN', 1, '2.00 TB', '1', 1, '2025-11-24 18:43:36', '2025-11-24 18:43:36'),
(15, '1697', 'itwtechnology', 1, '2.00 TB', '1', 1, '2025-11-24 18:43:36', '2025-11-24 18:43:36'),
(16, '203', 'CorpSoto', 1, '1.73 TB', '2', 1, '2025-11-24 18:43:36', '2025-11-24 18:43:36'),
(17, '1693', 'desarrollosresidencialesali', 1, '1.536 TB', '2', 1, '2025-11-24 18:43:36', '2025-11-24 18:43:36'),
(18, '284', 'Salle', 1, '1.41 TB', '14', 1, '2025-11-24 18:43:36', '2025-11-24 18:43:36'),
(19, '1602', 'Cesarmex', 1, '1.00 TB', '4', 1, '2025-11-24 18:43:36', '2025-11-24 18:43:36'),
(20, '1696', 'mccollect1696', 1, '1.00 TB', '0', 1, '2025-11-24 18:43:36', '2025-11-24 18:43:36'),
(21, '165', 'GUCE', 1, '750.00 GB', '0', 1, '2025-11-24 18:43:36', '2025-11-24 18:43:36'),
(22, '1688', 'Econatural', 1, '700.00 GB', '0', 1, '2025-11-24 18:43:36', '2025-11-24 18:43:36'),
(23, 'NO IDENTIFICADO', 'Jalucio', 1, '650.00 GB', '0', 1, '2025-11-24 18:43:36', '2025-11-24 18:43:36'),
(24, '1640', 'Winsnes', 1, '600.00 GB', '0', 1, '2025-11-24 18:43:36', '2025-11-24 18:43:36'),
(25, 'INTERNO', 'mvazquez', 2, '600.00 GB', '1', 1, '2025-11-24 18:43:36', '2025-11-24 18:43:36'),
(26, '1690', 'CAINTRA365', 1, '512.00 GB', '0', 1, '2025-11-24 18:43:36', '2025-11-24 18:43:36'),
(27, '1690', 'CAINTRAINM', 1, '512.00 GB', '0', 1, '2025-11-24 18:43:36', '2025-11-24 18:43:36'),
(28, '1689', 'UNOTIC365', 1, '500.00 GB', '0', 1, '2025-11-24 18:43:36', '2025-11-24 18:43:36'),
(29, '1689', 'UNOTICWORKSTATION', 1, '500.00 GB', '0', 1, '2025-11-24 18:43:36', '2025-11-24 18:43:36'),
(30, '1643', 'VELOX', 1, '500.00 GB', '1', 1, '2025-11-24 18:43:37', '2025-11-24 18:43:37'),
(31, '332', 'ranchosantarita', 1, '300.00 GB', '0', 1, '2025-11-24 18:43:37', '2025-11-24 18:43:37'),
(32, '325', 'vegasoft', 1, '200.00 GB', '1', 1, '2025-11-24 18:43:37', '2025-11-24 18:43:37'),
(33, '202', 'supollo', 1, '200.00 GB', '1', 1, '2025-11-24 18:43:37', '2025-11-24 18:43:37'),
(34, '60', 'accescom', 1, '150.00 GB', '1', 1, '2025-11-24 18:43:37', '2025-11-24 18:43:37'),
(35, 'INTERNO', 'lgranillo', 2, '100.00 GB', '0', 1, '2025-11-24 18:43:37', '2025-11-24 18:43:37'),
(36, 'INTERNO', 'mrojas', 2, '100.00 GB', '0', 1, '2025-11-24 18:43:37', '2025-11-24 18:43:37'),
(37, 'INTERNO', 'CSTEST', 2, '100.00 GB', '0', 1, '2025-11-24 18:43:37', '2025-11-24 18:43:37'),
(38, '322', 'rsoto_kuazar', 1, '70.00 GB', '1', 1, '2025-11-24 18:43:37', '2025-11-24 18:43:37'),
(39, 'INTERNO', 'rjimenez', 2, '10.00 GB', '0', 1, '2025-11-24 18:43:37', '2025-11-24 18:43:37'),
(40, '497', 'Antal', 1, '1.00 TB', '2', 1, '2025-11-24 18:43:37', '2025-11-24 18:43:37'),
(41, '402', 'Cdetallista', 1, '5 TB', '4', 1, '2025-11-24 18:43:37', '2025-11-24 18:43:37'),
(42, '422', 'CYBORG', 1, '750 GB', '5', 1, '2025-11-24 18:43:37', '2025-11-24 18:43:37'),
(43, '435', 'Fahorro', 1, '6656 GB', '38', 1, '2025-11-24 18:43:37', '2025-11-24 18:43:37'),
(44, '1655', 'EQUIPOS COMPUTACIONALES DE OCCIDENTE SA DE CV', 1, '2500 GB', '2', 1, '2025-11-24 18:43:37', '2025-11-24 18:43:37'),
(45, '492', 'Grafiady, S.A. de C.V.', 1, '200 GB', '2', 1, '2025-11-24 18:43:37', '2025-11-24 18:43:37'),
(46, '1683', 'GRUPO ESTRATEGIA POLITICA', 1, '478 GB', '5', 1, '2025-11-24 18:43:37', '2025-11-24 18:43:37'),
(47, '137', 'innasol', 1, '3800 GB', '8', 1, '2025-11-24 18:43:37', '2025-11-24 18:43:37'),
(48, '1593', 'MARUEI DE MEXICO SA DE CV', 1, '3 TB', '0', 1, '2025-11-24 18:43:37', '2025-11-24 18:43:37'),
(49, '255', 'SERVERWARE,S.A. DE C.V.', 1, '300 GB', '1', 1, '2025-11-24 18:43:37', '2025-11-24 18:43:37'),
(50, '85', 'sistemasdfkmx', 1, '400 GB', '0', 1, '2025-11-24 18:43:37', '2025-11-24 18:43:37'),
(51, '1605', 'SOLINCO, S.A. DE C.V.', 1, '350 GB', '0', 1, '2025-11-24 18:43:37', '2025-11-24 18:43:37'),
(52, '443', 'UnivOri', 1, '700.00 GB', '2', 1, '2025-11-24 18:43:37', '2025-11-24 18:43:37'),
(53, '115', 'provimpn', 1, '100.00 GB', '0', 1, '2025-11-24 18:43:37', '2025-11-24 18:43:37'),
(54, '240', 'colombinbel', 3, '5.00 TB', '3', 1, '2025-11-24 18:43:37', '2025-11-24 18:43:37'),
(55, '1555', 'pswglobsol', 3, '4.00 TB', '8', 1, '2025-11-24 18:43:37', '2025-11-24 18:43:37'),
(56, '1593', 'MARUEI', 3, '3.00 TB', '5', 1, '2025-11-24 18:43:37', '2025-11-24 18:43:37'),
(57, '318', 'siscontah', 3, '3.00 TB', '2', 1, '2025-11-24 18:43:37', '2025-11-24 18:43:37'),
(58, '1567', 'RSSTEC', 3, '2.05 TB', '1', 1, '2025-11-24 18:43:37', '2025-11-24 18:43:37'),
(59, '131', 'TEC01SW', 3, '1.00 TB', '0', 1, '2025-11-24 18:43:37', '2025-11-24 18:43:37'),
(60, '503', 'Prodexa', 3, '1.00 TB', '1', 1, '2025-11-24 18:43:37', '2025-11-24 18:43:37'),
(61, '502', 'AlimentosCarol', 3, '1.00 TB', '5', 1, '2025-11-24 18:43:37', '2025-11-24 18:43:37'),
(62, '513', 'ANTEQUERA', 3, '900.00 GB', '1', 1, '2025-11-24 18:43:37', '2025-11-24 18:43:37'),
(63, '167', 'TICC', 3, '650.00 GB', '0', 1, '2025-11-24 18:43:37', '2025-11-24 18:43:37'),
(64, '1564', 'INSASISSO', 3, '445.00 GB', '1', 1, '2025-11-24 18:43:37', '2025-11-24 18:43:37'),
(65, '492', 'Grafiady', 3, '200.00 GB', '2', 1, '2025-11-24 18:43:37', '2025-11-24 18:43:37'),
(66, '36', 'coelecpa', 3, '100.00 GB', '1', 1, '2025-11-24 18:43:37', '2025-11-24 18:43:37'),
(67, '488', 'HERMES', 3, '1.70 TB', '2', 1, '2025-11-24 18:43:37', '2025-11-24 18:43:37'),
(68, '141', 'MICROFORMAS', 3, '300.00 GB', '0', 1, '2025-11-24 18:43:37', '2025-11-24 18:43:37');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `info_guard`
--

CREATE TABLE `info_guard` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_user` int(11) NOT NULL,
  `dateInit` datetime NOT NULL,
  `dateFinish` datetime DEFAULT NULL,
  `status` int(1) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `info_guard`
--

INSERT INTO `info_guard` (`id`, `id_user`, `dateInit`, `dateFinish`, `status`, `created_at`, `updated_at`) VALUES
(152, 30, '2026-02-26 19:18:37', NULL, 1, '2026-02-27 01:18:37', '2026-02-27 01:18:37');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `microsoft_m`
--

CREATE TABLE `microsoft_m` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `serviceName` int(11) NOT NULL,
  `revisionDate` date NOT NULL,
  `state` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `ejecution` varchar(255) NOT NULL,
  `id_user` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `microsoft_m`
--

INSERT INTO `microsoft_m` (`id`, `serviceName`, `revisionDate`, `state`, `description`, `ejecution`, `id_user`, `created_at`, `updated_at`) VALUES
(1, 3, '2026-02-17', 2, 'prueba ok', 'todo ok', 31, '2026-02-21 00:30:54', '2026-02-21 00:30:54'),
(2, 4, '2026-02-02', 3, 'toidoo bie 1', 'toido bien 1', 31, '2026-02-21 00:32:45', '2026-02-21 00:32:45'),
(3, 6, '2026-01-28', 3, 'jbasfdbkjsdbjksjkasbdkjsa', 'otod bien 219292', 31, '2026-02-21 00:32:45', '2026-02-21 00:32:45'),
(4, 3, '2026-02-19', 2, 'sadsdasdsadadadsad', '8525fd55fsdf', 31, '2026-02-21 00:38:04', '2026-02-21 00:38:04'),
(5, 1, '2026-02-10', 3, 'fsdsdfsdff', 'fdfdfsff', 31, '2026-02-21 00:40:38', '2026-02-21 00:40:38'),
(6, 2, '2026-02-11', 2, 'gvsdf gfsdff fda asd', 'fdsdadasff', 31, '2026-02-21 00:41:07', '2026-02-21 00:41:07'),
(7, 2, '2026-02-11', 1, 'fdsadddads', 'fdsfdff', 31, '2026-02-21 00:46:13', '2026-02-21 00:46:13'),
(8, 5, '2026-02-11', 2, 'sadsadsadsadsadsad', 'ddsaadsadsadsadsad', 31, '2026-02-21 00:46:31', '2026-02-21 00:46:31'),
(9, 3, '2026-02-11', 1, 'sadsadsdadsa', 'ffdsad', 31, '2026-02-21 00:48:58', '2026-02-21 00:48:58'),
(10, 2, '2026-02-11', 2, 'sadsadsadsadsadasdaasdasdsada', 'sadsaddsad', 31, '2026-02-21 01:03:15', '2026-02-21 01:03:15'),
(11, 4, '2026-02-10', 2, 'cdsadsaddasddsadsadsadsadsa', 'dsadsadasd', 31, '2026-02-21 01:03:15', '2026-02-21 01:03:15'),
(12, 6, '2026-02-22', 2, 'CAIDA DE SERVIDOR', 'CAIDA DE SERVIDOR', 31, '2026-02-23 19:46:21', '2026-02-23 19:46:21'),
(13, 5, '2026-02-23', 1, 'NO HUBO INCIDENCIA.', 'TODO OK', 31, '2026-02-23 19:46:21', '2026-02-23 19:46:21');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(17, '2014_10_12_000000_create_users_table', 1),
(18, '2014_10_12_100000_create_password_reset_tokens_table', 1),
(19, '2019_08_19_000000_create_failed_jobs_table', 1),
(20, '2019_12_14_000001_create_personal_access_tokens_table', 1),
(21, '2025_12_18_111630_create_areas_table', 2),
(22, '2025_12_30_182138_create_tickets_table', 3),
(23, '2026_01_12_144145_create_monitoreos_table', 4),
(24, '2026_01_16_161547_create_sucursales_table', 5),
(25, '2026_02_20_144634_create_microsoft_m_table', 6),
(26, '2026_02_22_221015_create_monit_redes_table', 7);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `model_has_permissions`
--

CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `model_has_roles`
--

CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `model_has_roles`
--

INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES
(4, 'App\\Models\\User', 12),
(9, 'App\\Models\\User', 27),
(9, 'App\\Models\\User', 28),
(9, 'App\\Models\\User', 29),
(9, 'App\\Models\\User', 30),
(11, 'App\\Models\\User', 19),
(11, 'App\\Models\\User', 26),
(14, 'App\\Models\\User', 24),
(16, 'App\\Models\\User', 16),
(16, 'App\\Models\\User', 23),
(16, 'App\\Models\\User', 31);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `monitoreos`
--

CREATE TABLE `monitoreos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `siteApp` bigint(20) UNSIGNED NOT NULL,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `dateRest` date DEFAULT NULL,
  `estatus` int(11) NOT NULL,
  `observacion` text DEFAULT NULL,
  `concluido` tinyint(3) NOT NULL,
  `id_guard` bigint(20) UNSIGNED DEFAULT NULL,
  `user_Cre` bigint(20) UNSIGNED NOT NULL,
  `user_Upd` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `monitoreos`
--

INSERT INTO `monitoreos` (`id`, `siteApp`, `client_id`, `dateRest`, `estatus`, `observacion`, `concluido`, `id_guard`, `user_Cre`, `user_Upd`, `created_at`, `updated_at`) VALUES
(1, 1, 8, '2025-11-28', 4, 'Job en progreso', 1, NULL, 28, 28, '2025-12-17 14:35:08', '2026-01-30 18:05:21'),
(2, 1, 16, '2026-01-08', 3, 'Job finished with error at 11/11/2025 10:10\nError: Failed to update the backup encryption state. Failed to connect to the Veeam Cloud Connect service\nNo se pudo realizar una llamada a SSPI; consulte la excepción interna.\nEl identificador especificado no es válido', 1, 50, 28, 12, '2025-12-29 15:32:00', '2026-02-25 16:51:23'),
(3, 1, 14, '2026-01-18', 5, 'Repositorio saturado.', 1, 50, 27, 27, '2026-01-27 14:37:30', '2026-02-25 16:57:30'),
(4, 1, 18, '2026-02-06', 3, 'Se tienen 4 jobs fuera de secuencia se esta trabajando en hacer el ajuste para tenerlos en linea', 1, 50, 27, 27, '2026-02-02 14:55:00', '2026-02-25 23:48:03');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `monit_redes`
--

CREATE TABLE `monit_redes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `sucursal_id` int(11) NOT NULL,
  `dateRed` date NOT NULL,
  `statusRed` int(11) NOT NULL,
  `time_down` time DEFAULT NULL,
  `time_up` time DEFAULT NULL,
  `affectation` varchar(150) NOT NULL,
  `reason` text DEFAULT NULL,
  `note` varchar(200) DEFAULT NULL,
  `statusMonit` int(11) NOT NULL,
  `user_create` int(11) NOT NULL,
  `user_update` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `monit_redes`
--

INSERT INTO `monit_redes` (`id`, `sucursal_id`, `dateRed`, `statusRed`, `time_down`, `time_up`, `affectation`, `reason`, `note`, `statusMonit`, `user_create`, `user_update`, `created_at`, `updated_at`) VALUES
(1, 29, '2026-02-16', 3, '12:00:00', NULL, 'a la red unicamente', 'a la red unicamente', NULL, 1, 31, 31, '2026-02-23 18:11:22', '2026-02-23 18:11:22'),
(2, 29, '2026-02-12', 4, '12:05:00', NULL, 'tuvo impacto en sucursal', 'tuvo impacto en sucursal', NULL, 1, 31, 31, '2026-02-23 18:18:26', '2026-02-23 18:18:26'),
(3, 1, '2026-02-17', 4, '13:35:00', NULL, 'NO HUBO AFECTACION', 'TODO ESTA OK', 'SSSSSS', 1, 31, 31, '2026-02-23 19:52:18', '2026-02-23 19:52:18');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `password_reset_tokens`
--

INSERT INTO `password_reset_tokens` (`email`, `token`, `created_at`) VALUES
('avillavicencio@teamnet.com.mx', 'wLqVIUkYXRJzvzmEPuGKLxOqphPuEVQGK0nI9O7Xik4v3G3HhdrTAaQs9Hv5m9B4', '2026-02-10 18:05:36');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `id_area` int(11) DEFAULT NULL,
  `description` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `guard_name`, `id_area`, `description`, `created_at`, `updated_at`) VALUES
(7, 'users.export', 'api', NULL, 'Permite exportar usuarios', '2025-12-08 18:16:25', '2025-12-08 18:16:25'),
(8, 'users.browse', 'api', NULL, 'Método que permite navegar en la sección users.', '2025-12-08 18:27:27', '2025-12-08 18:27:27'),
(9, 'users.create', 'api', NULL, 'Método que permite visualizar la sección para crear un users.', '2025-12-08 18:27:27', '2025-12-08 18:27:27'),
(10, 'users.edit', 'api', NULL, 'Método que permite visualizar la sección para editar un users.', '2025-12-08 18:27:27', '2025-12-08 18:27:27'),
(11, 'users.delete', 'api', NULL, 'Método que permite eliminar un users.', '2025-12-08 18:27:27', '2025-12-08 18:27:27'),
(12, 'users.show', 'api', NULL, 'Método que permite visualizar los detalles de un users.', '2025-12-08 18:27:27', '2025-12-08 18:27:27'),
(13, 'console.browse', 'api', NULL, 'Método que permite navegar en la sección console.', '2025-12-08 19:23:53', '2025-12-08 19:23:53'),
(20, 'permisos.browse', 'api', NULL, 'Método que permite navegar en la sección permisos.', '2025-12-11 00:51:31', '2025-12-11 00:51:31'),
(21, 'permisos.create', 'api', NULL, 'Método que permite visualizar la sección para crear un permisos.', '2025-12-11 00:51:31', '2025-12-11 00:51:31'),
(22, 'permisos.edit', 'api', NULL, 'Método que permite visualizar la sección para editar un permisos.', '2025-12-11 00:51:31', '2025-12-11 00:51:31'),
(23, 'permisos.delete', 'api', NULL, 'Método que permite eliminar un permisos.', '2025-12-11 00:51:31', '2025-12-11 00:51:31'),
(25, 'roles.browse', 'api', NULL, 'Método que permite navegar en la sección roles.', '2025-12-11 17:52:15', '2025-12-11 17:52:15'),
(26, 'roles.create', 'api', NULL, 'Método que permite visualizar la sección para crear un roles.', '2025-12-11 17:52:15', '2025-12-11 17:52:15'),
(27, 'roles.edit', 'api', NULL, 'Método que permite visualizar la sección para editar un roles.', '2025-12-11 17:52:15', '2025-12-11 17:52:15'),
(28, 'roles.delete', 'api', NULL, 'Método que permite eliminar un roles.', '2025-12-11 17:52:15', '2025-12-11 17:52:15'),
(29, 'roles.show', 'api', NULL, 'Método que permite visualizar los detalles de un roles.', '2025-12-11 17:52:15', '2025-12-11 17:52:15'),
(34, 'users.stats', 'api', NULL, 'Permiso para ver las estadisticas', '2025-12-18 04:23:33', '2025-12-18 04:23:33'),
(35, 'area.browse', 'api', NULL, 'Método que permite navegar en la sección area.', '2025-12-18 17:47:26', '2025-12-18 17:47:26'),
(36, 'area.create', 'api', NULL, 'Método que permite visualizar la sección para crear un area.', '2025-12-18 17:47:26', '2025-12-18 17:47:26'),
(37, 'area.edit', 'api', NULL, 'Método que permite visualizar la sección para editar un area.', '2025-12-18 17:47:26', '2025-12-18 17:47:26'),
(38, 'area.delete', 'api', NULL, 'Método que permite eliminar un area.', '2025-12-18 17:47:26', '2025-12-18 17:47:26'),
(39, 'area.show', 'api', NULL, 'Método que permite visualizar los detalles de un area.', '2025-12-18 17:47:26', '2025-12-18 17:47:26'),
(40, 'clientnet.browse', 'api', 1, 'Método que permite navegar en la sección clientNet.', '2025-12-21 22:16:15', '2025-12-21 22:16:15'),
(41, 'clientnet.show', 'api', 1, 'Método que permite visualizar los detalles de un clientNet.', '2025-12-21 22:16:15', '2025-12-21 22:16:15'),
(42, 'clientveeam.browse', 'api', 1, 'Método que permite navegar en la sección clientveeam.', '2025-12-23 03:47:30', '2025-12-23 03:47:30'),
(43, 'clientveeam.create', 'api', 1, 'Método que permite visualizar la sección para crear un clientveeam.', '2025-12-23 03:47:30', '2025-12-23 03:47:30'),
(44, 'clientveeam.edit', 'api', 1, 'Método que permite visualizar la sección para editar un clientveeam.', '2025-12-23 03:47:30', '2025-12-23 03:47:30'),
(45, 'clientveeam.delete', 'api', 1, 'Método que permite eliminar un clientveeam.', '2025-12-23 03:47:30', '2025-12-23 03:47:30'),
(46, 'clientveeam.show', 'api', 1, 'Método que permite visualizar los detalles de un clientveeam.', '2025-12-23 03:47:30', '2025-12-23 03:47:30'),
(47, 'appclient.browse', 'api', 1, 'Método que permite navegar en la sección appclient.', '2025-12-23 23:39:49', '2025-12-23 23:39:49'),
(48, 'appclient.create', 'api', 1, 'Método que permite visualizar la sección para crear un appclient.', '2025-12-23 23:39:50', '2025-12-23 23:39:50'),
(49, 'appclient.delete', 'api', 1, 'Método que permite eliminar un appclient.', '2025-12-23 23:39:50', '2025-12-23 23:39:50'),
(50, 'appclient.edit', 'api', 1, 'Método que permite visualizar la sección para editar un appclient.', '2025-12-26 20:00:12', '2025-12-26 20:00:12'),
(54, 'guardias.browse', 'api', 1, 'Método que permite navegar en la sección guardias.', '2025-12-30 03:54:01', '2025-12-30 03:54:01'),
(55, 'guardias.create', 'api', 1, 'Método que permite visualizar la sección para crear un guardias.', '2025-12-30 03:54:01', '2025-12-30 03:54:01'),
(56, 'guardias.edit', 'api', 1, 'Método que permite visualizar la sección para editar un guardias.', '2025-12-30 03:54:01', '2025-12-30 03:54:01'),
(57, 'guardias.delete', 'api', 1, 'Método que permite eliminar un guardias.', '2025-12-30 03:54:01', '2025-12-30 03:54:01'),
(58, 'guardias.show', 'api', 1, 'Método que permite visualizar los detalles de un guardias.', '2025-12-30 03:54:01', '2025-12-30 03:54:01'),
(59, 'tickets.browse', 'api', 1, 'Método que permite navegar en la sección tickets.', '2025-12-31 00:00:50', '2025-12-31 00:00:50'),
(60, 'tickets.create', 'api', 1, 'Método que permite visualizar la sección para crear un tickets.', '2025-12-31 00:00:50', '2025-12-31 00:00:50'),
(61, 'tickets.edit', 'api', 1, 'Método que permite visualizar la sección para editar un tickets.', '2025-12-31 00:00:50', '2025-12-31 00:00:50'),
(62, 'tickets.delete', 'api', 1, 'Método que permite eliminar un tickets.', '2025-12-31 00:00:50', '2025-12-31 00:00:50'),
(63, 'tickets.show', 'api', 1, 'Método que permite visualizar los detalles de un tickets.', '2025-12-31 00:00:50', '2025-12-31 00:00:50'),
(64, 'monitoreos.browse', 'api', 1, 'Método que permite navegar en la sección monitoreos.', '2026-01-12 15:45:58', '2026-01-12 15:45:58'),
(65, 'monitoreos.create', 'api', 1, 'Método que permite visualizar la sección para crear un monitoreos.', '2026-01-12 15:45:58', '2026-01-12 15:45:58'),
(66, 'monitoreos.edit', 'api', 1, 'Método que permite visualizar la sección para editar un monitoreos.', '2026-01-12 15:45:58', '2026-01-12 15:45:58'),
(67, 'monitoreos.delete', 'api', 1, 'Método que permite eliminar un monitoreos.', '2026-01-12 15:45:58', '2026-01-12 15:45:58'),
(68, 'monitoreos.show', 'api', 1, 'Método que permite visualizar los detalles de un monitoreos.', '2026-01-12 15:45:58', '2026-01-12 15:45:58'),
(69, 'sucursales.browse', 'api', 4, 'Método que permite navegar en la sección sucursales.', '2026-01-16 21:44:09', '2026-01-16 21:44:09'),
(70, 'sucursales.create', 'api', 4, 'Método que permite visualizar la sección para crear un sucursales.', '2026-01-16 21:44:09', '2026-01-16 21:44:09'),
(71, 'sucursales.edit', 'api', 4, 'Método que permite visualizar la sección para editar un sucursales.', '2026-01-16 21:44:09', '2026-01-16 21:44:09'),
(72, 'sucursales.delete', 'api', 4, 'Método que permite eliminar un sucursales.', '2026-01-16 21:44:09', '2026-01-16 21:44:09'),
(73, 'sucursales.show', 'api', 4, 'Método que permite visualizar los detalles de un sucursales.', '2026-01-16 21:44:09', '2026-01-16 21:44:09'),
(74, 'microsoft.browse', 'api', 4, 'Método que permite navegar en la sección microsoft.', '2026-01-21 18:03:22', '2026-01-21 18:03:22'),
(75, 'microsoft.create', 'api', 4, 'Método que permite visualizar la sección para crear un microsoft.', '2026-01-21 18:03:22', '2026-01-21 18:03:22'),
(76, 'microsoft.edit', 'api', 4, 'Método que permite visualizar la sección para editar un microsoft.', '2026-01-21 18:03:22', '2026-01-21 18:03:22'),
(77, 'microsoft.delete', 'api', 4, 'Método que permite eliminar un microsoft.', '2026-01-21 18:03:22', '2026-01-21 18:03:22'),
(78, 'microsoft.show', 'api', 4, 'Método que permite visualizar los detalles de un microsoft.', '2026-01-21 18:03:22', '2026-01-21 18:03:22'),
(79, 'monit-aa.browse', 'api', 4, 'Método que permite navegar en la sección monit-aa.', '2026-02-23 03:53:02', '2026-02-23 03:53:02'),
(80, 'monit-aa.create', 'api', 4, 'Método que permite visualizar la sección para crear un monit-aa.', '2026-02-23 03:53:02', '2026-02-23 03:53:02'),
(81, 'monit-aa.edit', 'api', 4, 'Método que permite visualizar la sección para editar un monit-aa.', '2026-02-23 03:53:02', '2026-02-23 03:53:02'),
(82, 'monit-aa.delete', 'api', 4, 'Método que permite eliminar un monit-aa.', '2026-02-23 03:53:02', '2026-02-23 03:53:02'),
(83, 'monit-aa.show', 'api', 4, 'Método que permite visualizar los detalles de un monit-aa.', '2026-02-23 03:53:02', '2026-02-23 03:53:02');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(4, 'Administrador', 'api', '2025-12-06 03:55:58', '2025-12-06 03:55:58'),
(9, 'Cloud Services Support', 'api', '2025-12-11 20:37:43', '2025-12-11 20:37:43'),
(11, 'Service Support Cloud Coordinator', 'api', '2025-12-19 18:09:00', '2025-12-19 18:09:00'),
(14, 'Infraestructura 1', 'api', '2025-12-19 18:10:44', '2025-12-19 18:10:44'),
(15, 'Infraestructura 2', 'api', '2025-12-19 18:10:56', '2025-12-19 18:10:56'),
(16, 'Comunicaciones 1', 'api', '2025-12-19 18:11:09', '2025-12-19 18:11:09'),
(17, 'Comunicaciones 2', 'api', '2025-12-19 18:11:20', '2025-12-19 18:11:20'),
(20, 'prueba capas', 'api', '2025-12-26 22:45:20', '2025-12-26 22:45:20');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `role_has_permissions`
--

CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `role_has_permissions`
--

INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES
(7, 4),
(7, 11),
(8, 4),
(8, 11),
(8, 14),
(8, 15),
(8, 16),
(8, 17),
(9, 4),
(9, 11),
(10, 4),
(10, 11),
(11, 4),
(12, 4),
(12, 11),
(13, 4),
(13, 7),
(20, 4),
(21, 4),
(22, 4),
(23, 4),
(25, 4),
(25, 11),
(25, 16),
(26, 4),
(27, 4),
(27, 11),
(27, 16),
(28, 4),
(29, 4),
(29, 11),
(34, 4),
(34, 11),
(35, 4),
(35, 20),
(36, 4),
(37, 4),
(38, 4),
(39, 4),
(40, 4),
(40, 9),
(40, 11),
(41, 4),
(41, 9),
(41, 11),
(42, 4),
(42, 9),
(42, 11),
(43, 4),
(43, 9),
(43, 11),
(44, 4),
(44, 11),
(45, 4),
(46, 4),
(46, 9),
(46, 11),
(47, 4),
(47, 9),
(47, 11),
(48, 4),
(48, 9),
(48, 11),
(49, 4),
(50, 4),
(50, 11),
(54, 4),
(54, 9),
(54, 11),
(55, 4),
(55, 9),
(55, 11),
(56, 4),
(56, 9),
(56, 11),
(57, 4),
(58, 4),
(58, 9),
(58, 11),
(59, 4),
(59, 9),
(59, 11),
(60, 4),
(60, 9),
(60, 11),
(61, 4),
(61, 9),
(61, 11),
(62, 4),
(63, 4),
(63, 9),
(63, 11),
(64, 4),
(64, 9),
(64, 11),
(65, 4),
(65, 9),
(65, 11),
(66, 4),
(66, 9),
(66, 11),
(67, 4),
(68, 4),
(68, 9),
(68, 11),
(69, 4),
(69, 16),
(70, 4),
(70, 16),
(71, 4),
(71, 16),
(72, 4),
(73, 4),
(73, 16),
(74, 4),
(74, 16),
(75, 4),
(75, 16),
(76, 4),
(76, 16),
(77, 4),
(77, 16),
(78, 4),
(78, 16),
(79, 4),
(79, 16),
(80, 4),
(80, 16),
(81, 4),
(81, 16),
(82, 4),
(83, 4),
(83, 16);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sucursales`
--

CREATE TABLE `sucursales` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nameS` int(11) DEFAULT NULL,
  `servHost` varchar(255) NOT NULL,
  `plat` int(11) NOT NULL,
  `keys` varchar(255) DEFAULT NULL,
  `ip` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `sucursales`
--

INSERT INTO `sucursales` (`id`, `nameS`, `servHost`, `plat`, `keys`, `ip`, `created_at`, `updated_at`) VALUES
(1, 2, 'AP-Guadalajara-01', 1, NULL, '192.168.10.64', '2026-01-20 21:27:31', '2026-02-23 18:27:14'),
(2, 1, 'Ventas02', 1, NULL, '192.168.160.35', '2026-01-20 22:54:44', '2026-01-20 22:54:44'),
(3, 2, 'AP-Guadalajara-04', 1, NULL, '192.168.10.54', '2026-01-20 23:14:03', '2026-01-20 23:14:03'),
(4, 1, 'Cecap01', 1, NULL, '192.168.160.47', '2026-01-20 23:14:18', '2026-01-20 23:14:18'),
(5, 1, 'Direccion', 1, NULL, '192.168.160.37', '2026-01-20 23:14:57', '2026-01-20 23:14:57'),
(6, 1, 'Nave01', 1, NULL, '192.168.160.33', '2026-01-20 23:15:25', '2026-01-20 23:15:25'),
(7, 1, 'Finanzas01', 1, NULL, '192.168.160.39', '2026-01-20 23:15:46', '2026-01-20 23:15:46'),
(8, 3, 'AP-Monterrey-04', 1, NULL, '192.168.30.19', '2026-01-20 23:16:01', '2026-01-20 23:16:01'),
(9, 1, 'Sistemas', 1, NULL, '192.168.160.45', '2026-01-21 01:12:42', '2026-01-21 01:12:42'),
(10, 1, 'Kodak', 1, NULL, '192.168.160.42', '2026-01-21 01:12:42', '2026-01-21 01:12:42'),
(11, 3, 'AP-Monterrey-01', 1, NULL, '192.168.30.21', '2026-01-21 01:12:42', '2026-01-21 01:12:42'),
(12, 2, 'AP-Guadalajara-02', 1, NULL, '192.168.10.62', '2026-01-21 01:12:42', '2026-01-21 01:12:42'),
(13, 4, 'AP-Merida', 1, NULL, '192.168.20.130', '2026-01-21 01:12:42', '2026-01-21 01:12:42'),
(14, 3, 'AP-Monterrey-00', 1, NULL, '192.168.30.20', '2026-01-21 01:12:42', '2026-01-21 01:12:42'),
(15, 2, 'AP-Guadalajara-03', 1, NULL, '192.168.10.55', '2026-01-21 01:12:42', '2026-01-21 01:12:42'),
(16, 1, 'Marel', 1, NULL, '192.168.160.43', '2026-01-21 01:12:42', '2026-01-21 01:12:42'),
(17, 1, 'Ventas01', 1, NULL, '192.168.161.123', '2026-01-21 01:12:42', '2026-01-21 01:12:42'),
(18, 1, 'Operaciones', 1, NULL, '192.168.160.40', '2026-01-21 01:12:42', '2026-01-21 01:12:42'),
(19, 1, 'Cecap02', 1, NULL, '192.168.160.46', '2026-01-21 01:12:42', '2026-01-21 01:12:42'),
(20, 1, 'Iker', 1, NULL, '192.168.160.41', '2026-01-21 01:12:42', '2026-01-21 01:12:42'),
(21, 3, 'AP-Monterrey-03', 1, NULL, '192.168.30.23', '2026-01-21 01:12:42', '2026-01-21 01:12:42'),
(22, 1, 'Sala Scalling Up', 1, NULL, '192.168.160.38', '2026-01-21 01:12:42', '2026-01-21 01:12:42'),
(23, 1, 'Finanzas02', 1, NULL, '192.168.160.34', '2026-01-21 01:12:42', '2026-01-21 01:12:42'),
(24, 1, 'Finanzas03', 1, NULL, '192.168.160.36', '2026-01-21 01:12:42', '2026-01-21 01:12:42'),
(25, 1, 'Primario Valle', 2, '1igxv4b', NULL, '2026-01-21 04:19:31', '2026-01-21 04:19:31'),
(26, 2, 'Primario Guadalajara', 2, '1k0rd2p', NULL, '2026-01-21 04:19:31', '2026-01-21 04:19:31'),
(27, 3, 'Primario Monterrey', 2, '1-1tg5w8x', NULL, '2026-01-21 04:19:31', '2026-01-21 04:19:31'),
(28, 1, 'Secundario Valle', 2, '1-1tg3jbf', NULL, '2026-01-21 04:19:31', '2026-01-21 04:19:31'),
(29, 4, 'Merida', 2, '1-1igyhsm', NULL, '2026-01-21 04:19:31', '2026-01-21 05:35:25');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tickets`
--

CREATE TABLE `tickets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `numTicket` bigint(20) UNSIGNED NOT NULL,
  `numTicketNoct` bigint(20) UNSIGNED DEFAULT NULL,
  `user_create_ticket` bigint(20) UNSIGNED NOT NULL,
  `assigned_user_id` bigint(20) UNSIGNED NOT NULL,
  `titleTicket` varchar(100) NOT NULL,
  `descriptionTicket` text NOT NULL,
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `id_guardia` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tickets`
--

INSERT INTO `tickets` (`id`, `numTicket`, `numTicketNoct`, `user_create_ticket`, `assigned_user_id`, `titleTicket`, `descriptionTicket`, `status`, `id_guardia`, `created_at`, `updated_at`) VALUES
(1, 8495, 8633, 28, 26, '8495', 'Se agenda sesión para el día martes a las 16:00 hrs, respecto a los Jobs el día de ayer se envió evidencia a Hercy de los Jobs completados correctamente.', 1, NULL, '2026-01-31 15:02:56', '2026-01-31 15:02:56'),
(2, 8646, 8710, 28, 28, '8646', 'El equipo de arquitectura busca implementar una nueva configuración para solucionar el tema del 2FA, el cliente nos comenta que después de las 15:00 hrs se puede agendar una sesión, en espera de la disponibilidad de Luis Granillo, Didider y Fer. Buscar a Luis para que nos proporcionará el horario disponible para agendar la sesión y exponer la solución con el cliente para conocer si acepta la propuesta o continua trabajando como actualmente lo hace.', 1, NULL, '2026-02-10 15:17:39', '2026-02-12 15:26:04'),
(3, 8700, 8710, 30, 30, '8700', 'Se envía respuesta buscando la aceptación del cliente para añadir en los servers 1 y 2 una unidad compartida por red para que los usuarios solicitados visualicen la carpeta facturación alojada en el server 4 por medio del html.', 1, NULL, '2026-02-12 15:26:04', '2026-02-16 15:25:42'),
(4, 8693, 8710, 30, 30, '8693', 'Se agenda sesión con cliente el día de hoy a las 14:30 hrs.', 1, NULL, '2026-02-12 15:26:04', '2026-02-16 15:25:42'),
(5, 8611, 8710, 30, 30, '8611', 'En espera de disponibilidad para reactivar agentes.', 1, NULL, '2026-02-12 15:26:04', '2026-02-16 15:25:42'),
(6, 8495, 8710, 28, 26, '8495', 'Se abordara sesión el día viernes con el equipo de Veeam.', 1, NULL, '2026-02-12 15:26:04', '2026-02-12 15:26:04'),
(8, 8611, NULL, 26, 30, 'Re: Por favor, díganos lo que piensa de nuestro servicio', 'Sesión dia lunes 9 pm.', 1, NULL, '2026-02-16 19:40:32', '2026-02-16 19:40:32'),
(9, 8495, NULL, 27, 26, 'Validacion de Jobs', 'Se envia reporte el dia viernes, pendiente seguimiento a sobreescritura de puntos', 1, 50, '2026-02-16 19:40:34', '2026-02-16 19:40:34'),
(10, 8343, NULL, 27, 26, 'Error de recuperación de maquinas Linux', 'Se envia logs al equipo de veeam, seguimiento a ticket', 1, 50, '2026-02-16 19:40:34', '2026-02-16 19:40:34'),
(14, 85231, NULL, 19, 27, 'dashbopard', 'dfsfsdfdffsd', 1, NULL, '2026-02-17 16:41:28', '2026-02-25 23:45:21');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `area_id` int(11) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `avatar` varchar(255) DEFAULT 'userdefault.jpg',
  `Activo` tinyint(1) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `area_id`, `email_verified_at`, `password`, `avatar`, `Activo`, `remember_token`, `last_login_at`, `created_at`, `updated_at`) VALUES
(12, 'Alfredo Villavicencio Luis', 'avillavicencio@teamnet.com.mx', NULL, NULL, '$2y$12$9oX8oCaZ0gpLtcoieL2FAOZ0kMBXhSAgVoCEEXhBP3AsLunvoOuSa', 'https://fastrdjgttfnqkggxhmu.supabase.co/storage/v1/object/public/Avatars/12-1767072008324.webp', 1, NULL, '2026-02-27 05:34:26', '2025-12-05 06:43:48', '2026-02-27 05:34:26'),
(16, 'Bernardo Jast', 'rolando.durgan@example.net', 4, '2025-12-05 22:10:14', '$2y$12$GeIP67KWH9oIQiAGgEk6zeLaNRA4YpIATdUHWj3FImhcsmMheJ4Rm', 'https://fastrdjgttfnqkggxhmu.supabase.co/storage/v1/object/public/Avatars/userdefault.jpg', 1, 'brfsLoHBaW', '2026-01-16 19:27:39', '2025-12-05 22:10:14', '2026-01-16 19:27:39'),
(19, 'Alfredo Villavicencio No Admin', 'alfre1230999@gmail.com', 1, NULL, '$2y$12$O86jv0RpspWBBq/iq.YZMu2Anrb6WZhHBugXCAGHUPkb23z84ZEeq', 'https://fastrdjgttfnqkggxhmu.supabase.co/storage/v1/object/public/Avatars/19-1766204795492.webp', 1, NULL, '2025-12-26 20:30:22', '2025-12-13 04:03:40', '2026-01-02 18:53:34'),
(23, 'Pruebas Lopez', 'prueba@pruebaLo.com', 4, NULL, '$2y$12$12w69BfzkFjS7C8Y0cLoeOTXK29zCSM/FfSjLufk8kd/K6gufIbeW', 'https://fastrdjgttfnqkggxhmu.supabase.co/storage/v1/object/public/Avatars/userdefault.jpg', 1, NULL, NULL, '2025-12-19 18:40:42', '2025-12-19 18:40:42'),
(24, 'Pruebas Gomez', 'prueba@pruebaGo.com', 2, NULL, '$2y$12$HlUDy7GneveFbZM3Qk7HSew.t/ITgr8O0boM9u36bgb1fJL8AjAfK', 'https://fastrdjgttfnqkggxhmu.supabase.co/storage/v1/object/public/Avatars/userdefault.jpg', 1, NULL, NULL, '2025-12-19 18:41:37', '2025-12-19 19:10:40'),
(26, 'Eduardo Flores Santiago', 'eduardo.flores@stratospherecorp.com', 1, NULL, '$2y$12$ZB.9fVCi7NCKkTIEjvFYaOT.YqPiS91Gyb8Iz6D9TAjxzL1J2uoz2', 'https://fastrdjgttfnqkggxhmu.supabase.co/storage/v1/object/public/Avatars/26-1769107169999.webp', 1, NULL, '2026-02-10 18:11:00', '2025-12-29 16:48:36', '2026-02-10 18:11:00'),
(27, 'Dilan Martínez Escobedo', 'dilan.martinez@stratospherecorp.com', 1, NULL, '$2y$12$4/S1xrBd4SSalCv7u9jtCO1mU0x5oC54jE6ljQwNdvX33SwPy05jO', 'https://fastrdjgttfnqkggxhmu.supabase.co/storage/v1/object/public/Avatars/userdefault.jpg', 1, NULL, '2026-02-25 23:43:45', '2025-12-29 16:52:24', '2026-02-25 23:43:45'),
(28, 'Miguel Rojas Romero', 'miguel.rojas@stratospherecorp.com', 1, NULL, '$2y$12$RRDgVk4HQEFOXL1/omNz8OppgK8Dr1OthScClCz9lwQgtgazPFm/e', 'https://fastrdjgttfnqkggxhmu.supabase.co/storage/v1/object/public/Avatars/userdefault.jpg', 1, NULL, '2025-12-30 18:25:24', '2025-12-30 04:09:51', '2025-12-30 18:25:24'),
(29, 'Miguel Segundo Sebastián', 'miguel.segundo@stratospherecorp.com', 1, NULL, '$2y$12$3D7lhuU9YiYDHaI4NLS68.T84o2fm3XHXNJ604pGPFHkdBH9wmW6C', 'https://fastrdjgttfnqkggxhmu.supabase.co/storage/v1/object/public/Avatars/userdefault.jpg', 1, NULL, NULL, '2025-12-30 04:10:38', '2025-12-30 04:10:38'),
(30, 'Josué Flores Ramírez', 'josue.flores@stratospherecorp.com', 1, NULL, '$2y$12$O0B3p.mqjhWckfwbPho.BueTVqa59p.npOaeNUi6UAgIEYorAm8ju', 'https://fastrdjgttfnqkggxhmu.supabase.co/storage/v1/object/public/Avatars/userdefault.jpg', 1, NULL, NULL, '2026-02-16 18:51:39', '2026-02-16 18:51:39'),
(31, 'Ricardo Estrada Loza', 'restrada@teamnet.com.mx', 4, NULL, '$2y$12$L57ZDfsrym8Y6n10IM3MOeO.lm95i1PdaY6TW1khd38YhR.0JHcku', 'https://ztbplugqqtemidsmbmoy.supabase.co/storage/v1/object/public/Avatars/userdefault.jpg', 1, NULL, '2026-02-23 19:30:48', '2026-02-20 18:41:26', '2026-02-23 19:30:48');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `app_service`
--
ALTER TABLE `app_service`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_app_service_name` (`nameService`);

--
-- Indices de la tabla `areas`
--
ALTER TABLE `areas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `areas_name_unique` (`name`);

--
-- Indices de la tabla `c_veeam`
--
ALTER TABLE `c_veeam`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cveeam_activo_name` (`activo`,`nameCV`),
  ADD KEY `idx_cveeam_app` (`app`),
  ADD KEY `idx_cveeam_num` (`numCV`);

--
-- Indices de la tabla `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indices de la tabla `info_guard`
--
ALTER TABLE `info_guard`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_info_guard_user_status_date` (`id_user`,`status`,`dateInit`);

--
-- Indices de la tabla `microsoft_m`
--
ALTER TABLE `microsoft_m`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  ADD KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indices de la tabla `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  ADD KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indices de la tabla `monitoreos`
--
ALTER TABLE `monitoreos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_concluido_created` (`concluido`,`created_at`),
  ADD KEY `idx_siteApp` (`siteApp`),
  ADD KEY `idx_client_id` (`client_id`),
  ADD KEY `idx_user_cre` (`user_Cre`),
  ADD KEY `idx_user_upd` (`user_Upd`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_client_site_dateRest` (`client_id`,`siteApp`,`dateRest`);

--
-- Indices de la tabla `monit_redes`
--
ALTER TABLE `monit_redes`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indices de la tabla `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indices de la tabla `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indices de la tabla `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`role_id`);

--
-- Indices de la tabla `sucursales`
--
ALTER TABLE `sucursales`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tickets_numticket_index` (`numTicket`),
  ADD KEY `tickets_numticketnoct_index` (`numTicketNoct`),
  ADD KEY `tickets_user_create_ticket_index` (`user_create_ticket`),
  ADD KEY `tickets_assigned_user_id_index` (`assigned_user_id`),
  ADD KEY `tickets_id_guardia_index` (`id_guardia`),
  ADD KEY `tickets_status_index` (`status`),
  ADD KEY `tickets_created_at_index` (`created_at`),
  ADD KEY `tickets_user_create_created_at_idx` (`user_create_ticket`,`created_at`),
  ADD KEY `tickets_assigned_created_at_idx` (`assigned_user_id`,`created_at`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD KEY `idx_users_name` (`name`),
  ADD KEY `idx_users_email` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `app_service`
--
ALTER TABLE `app_service`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `areas`
--
ALTER TABLE `areas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `c_veeam`
--
ALTER TABLE `c_veeam`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT de la tabla `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `info_guard`
--
ALTER TABLE `info_guard`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=153;

--
-- AUTO_INCREMENT de la tabla `microsoft_m`
--
ALTER TABLE `microsoft_m`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT de la tabla `monitoreos`
--
ALTER TABLE `monitoreos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `monit_redes`
--
ALTER TABLE `monit_redes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT de la tabla `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `sucursales`
--
ALTER TABLE `sucursales`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT de la tabla `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
