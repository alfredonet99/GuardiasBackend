-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 07-01-2026 a las 06:21:22
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
(5, 'FIREWALL', 'PRUEBA LISTA', 0, '2025-12-29 18:11:28', '2026-01-05 17:51:15');

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
(1, 27, '2025-07-31 01:08:17', NULL, 3, '2025-07-31 19:08:17', '2025-12-30 18:38:59'),
(2, 26, '2025-08-04 00:34:09', '2025-08-04 09:38:00', 2, '2025-08-04 18:34:09', '2025-08-05 03:39:03'),
(4, 28, '2025-08-04 18:39:38', '2025-08-05 09:05:00', 3, '2025-08-05 12:39:38', '2025-08-06 03:07:03'),
(5, 27, '2025-08-05 15:04:04', '2025-08-06 09:01:00', 2, '2025-08-06 09:04:04', '2025-08-07 03:01:38'),
(6, 29, '2025-08-07 18:35:10', '2025-08-08 09:26:00', 2, '2025-08-08 12:35:10', '2025-08-09 03:30:01'),
(7, 27, '2025-08-11 18:35:21', '2025-08-12 09:00:00', 2, '2025-08-12 12:35:21', '2025-08-13 03:03:04'),
(8, 29, '2025-08-13 18:29:20', '2025-08-14 09:19:00', 2, '2025-08-14 12:29:20', '2025-08-15 03:19:41'),
(9, 27, '2025-08-14 17:45:30', '2025-12-29 22:07:01', 3, '2025-08-15 11:45:30', '2025-08-16 03:01:37'),
(10, 26, '2025-08-15 19:01:57', '2025-08-16 09:22:00', 2, '2025-08-16 13:01:57', '2025-08-17 03:24:26'),
(11, 27, '2025-08-16 08:59:48', '2025-08-17 09:00:00', 2, '2025-08-17 02:59:48', '2025-08-18 03:00:37'),
(12, 26, '2025-08-17 09:07:10', '2025-08-17 23:59:00', 2, '2025-08-18 03:07:10', '2025-08-18 18:01:17'),
(13, 22, '2025-10-08 10:19:11', '2025-10-13 17:48:00', 2, '2025-10-09 04:19:11', '2025-10-14 12:21:29'),
(14, 22, '2025-10-13 18:28:13', '2025-10-13 18:32:00', 2, '2025-10-14 12:28:13', '2025-10-14 12:47:14'),
(15, 22, '2025-10-13 18:52:41', '2025-10-13 18:52:00', 2, '2025-10-14 12:52:41', '2025-10-14 12:53:12'),
(16, 27, '2025-11-18 23:27:27', '2025-12-24 12:58:01', 2, '2025-11-19 17:27:27', '2025-11-19 17:27:27'),
(17, 22, '2025-11-24 21:19:34', '2025-12-29 22:07:06', 2, '2025-11-25 03:19:34', '2025-11-25 03:19:34'),
(18, 27, '2025-12-26 09:30:08', '2025-12-26 09:30:00', 2, '2025-12-26 15:30:08', '2025-12-26 15:30:35'),
(19, 26, '2025-12-30 12:19:31', '2025-12-30 12:39:02', 3, '2025-12-30 18:19:31', '2025-12-30 18:39:02'),
(20, 26, '2025-12-30 12:45:53', '2025-12-30 12:47:05', 3, '2025-12-30 18:45:53', '2025-12-30 18:47:05'),
(21, 26, '2025-12-30 12:52:33', '2025-12-30 13:08:00', 3, '2025-12-30 18:52:33', '2025-12-30 19:08:00'),
(22, 27, '2025-12-30 17:37:52', '2026-01-02 12:00:01', 3, '2025-12-30 23:37:52', '2026-01-02 18:00:01'),
(23, 26, '2026-01-02 14:11:27', '2026-01-05 12:00:01', 3, '2026-01-02 20:11:27', '2026-01-05 18:00:01'),
(24, 26, '2026-01-05 16:43:39', NULL, 1, '2026-01-05 22:43:39', '2026-01-05 22:43:39');

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
(22, '2025_12_30_182138_create_tickets_table', 3);

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
(11, 'App\\Models\\User', 19),
(11, 'App\\Models\\User', 26),
(14, 'App\\Models\\User', 24),
(16, 'App\\Models\\User', 16),
(16, 'App\\Models\\User', 23);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(63, 'tickets.show', 'api', 1, 'Método que permite visualizar los detalles de un tickets.', '2025-12-31 00:00:50', '2025-12-31 00:00:50');

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
(63, 11);

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
(1, 1, NULL, 26, 27, 'Prueba de ticket', 'Ticket creado desde tinker para validar flujo.', 1, NULL, '2025-12-31 00:49:43', '2025-12-31 00:49:43'),
(2, 1001, NULL, 27, 27, 'Prueba Postman', 'Creando ticket y detectando guardia activa si existe.', 2, 22, '2025-12-31 01:52:44', '2025-12-31 01:52:44'),
(3, 12345, NULL, 19, 27, 'PRUEBA BUENA', 'TODO VA A SALIR BIENB', 1, NULL, '2026-01-02 21:35:32', '2026-01-02 21:35:32'),
(4, 789, 123, 19, 28, 'PRUEBA BUENA 2', 'HOLA, BIEN', 1, NULL, '2026-01-02 21:44:08', '2026-01-02 21:44:08'),
(5, 5214, 632, 19, 28, 'HFR4FA', 'FFDSFFSD', 1, NULL, '2026-01-02 21:44:08', '2026-01-06 03:12:13'),
(6, 4365, NULL, 26, 19, 'PRUEBA LALO', 'PRUEBAS HECHAS POR LALO', 1, 23, '2026-01-02 21:59:44', '2026-01-02 21:59:44'),
(7, 98765, 456789, 26, 27, 'PRUEBA UNA VEZ MAS', 'PRUEBA UNA VEZ MAS', 1, 23, '2026-01-02 22:45:31', '2026-01-02 22:45:31'),
(8, 7892, 4562, 26, 28, 'PRUEBA UNA VEZ MAS  2.0', 'PRUEBA UNA VEZ MAS  2.0', 1, 23, '2026-01-02 22:45:32', '2026-01-02 22:45:32'),
(9, 78964, 45666, 26, 27, 'PRUEBA UNA VEZ MAS  3.0', '452254', 1, 23, '2026-01-02 22:45:32', '2026-01-02 22:45:32'),
(10, 76555, NULL, 26, 19, 'NO DEPENDE DEL FROTN LALO', 'NO DEPENDE DEL FROTN LALO', 1, 23, '2026-01-02 23:10:07', '2026-01-02 23:10:07'),
(11, 7985542, 5561455, 26, 28, 'NO DEPENDE DEL FROTN LALO 2', 'NO DEPENDE DEL FROTN LALO', 1, 23, '2026-01-02 23:10:07', '2026-01-02 23:10:07'),
(12, 5252, 5252, 28, 27, 'operaciones/tickets', 'operaciones/tickets', 1, NULL, '2026-01-02 23:12:51', '2026-01-02 23:12:51'),
(13, 342334, 3432423, 26, 19, '423423423434234', 'dsaddadsdasdsa', 1, 23, '2026-01-02 23:18:50', '2026-01-02 23:18:50'),
(14, 714835, NULL, 27, 26, 'ESTO SE EJEUCTA TAMBIEN OK', 'SE EJECUTA CORRECTAMENTE', 1, NULL, '2026-01-02 23:20:24', '2026-01-06 01:30:22'),
(15, 739182, 963, 27, 26, 'update correcto', 'update correcto', 1, NULL, '2026-01-06 01:30:15', '2026-01-06 05:04:58');

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
(12, 'Alfredo Villavicencio Luis', 'avillavicencio@teamnet.com.mx', NULL, NULL, '$2y$12$9oX8oCaZ0gpLtcoieL2FAOZ0kMBXhSAgVoCEEXhBP3AsLunvoOuSa', 'https://fastrdjgttfnqkggxhmu.supabase.co/storage/v1/object/public/Avatars/12-1767072008324.webp', 1, NULL, '2026-01-06 05:01:29', '2025-12-05 06:43:48', '2026-01-06 05:01:29'),
(16, 'Bernardo Jast', 'rolando.durgan@example.net', 4, '2025-12-05 22:10:14', '$2y$12$GeIP67KWH9oIQiAGgEk6zeLaNRA4YpIATdUHWj3FImhcsmMheJ4Rm', 'https://fastrdjgttfnqkggxhmu.supabase.co/storage/v1/object/public/Avatars/userdefault.jpg', 1, 'brfsLoHBaW', '2025-12-30 23:33:54', '2025-12-05 22:10:14', '2025-12-30 23:33:54'),
(19, 'Alfredo Villavicencio No Admin', 'alfre1230999@gmail.com', 1, NULL, '$2y$12$O86jv0RpspWBBq/iq.YZMu2Anrb6WZhHBugXCAGHUPkb23z84ZEeq', 'https://fastrdjgttfnqkggxhmu.supabase.co/storage/v1/object/public/Avatars/19-1766204795492.webp', 1, NULL, '2025-12-26 20:30:22', '2025-12-13 04:03:40', '2026-01-02 18:53:34'),
(23, 'Pruebas Lopez', 'prueba@pruebaLo.com', 4, NULL, '$2y$12$12w69BfzkFjS7C8Y0cLoeOTXK29zCSM/FfSjLufk8kd/K6gufIbeW', 'https://fastrdjgttfnqkggxhmu.supabase.co/storage/v1/object/public/Avatars/userdefault.jpg', 1, NULL, NULL, '2025-12-19 18:40:42', '2025-12-19 18:40:42'),
(24, 'Pruebas Gomez', 'prueba@pruebaGo.com', 2, NULL, '$2y$12$HlUDy7GneveFbZM3Qk7HSew.t/ITgr8O0boM9u36bgb1fJL8AjAfK', 'https://fastrdjgttfnqkggxhmu.supabase.co/storage/v1/object/public/Avatars/userdefault.jpg', 1, NULL, NULL, '2025-12-19 18:41:37', '2025-12-19 19:10:40'),
(26, 'Eduardo Flores Santiago', 'eduardo.flores@stratospherecorp.com', 1, NULL, '$2y$12$ZB.9fVCi7NCKkTIEjvFYaOT.YqPiS91Gyb8Iz6D9TAjxzL1J2uoz2', 'https://fastrdjgttfnqkggxhmu.supabase.co/storage/v1/object/public/Avatars/userdefault.jpg', 1, NULL, '2026-01-06 02:27:51', '2025-12-29 16:48:36', '2026-01-06 02:27:51'),
(27, 'Dilan Martínez Escobedo', 'dilan.martinez@stratospherecorp.com', 1, NULL, '$2y$12$4/S1xrBd4SSalCv7u9jtCO1mU0x5oC54jE6ljQwNdvX33SwPy05jO', 'https://fastrdjgttfnqkggxhmu.supabase.co/storage/v1/object/public/Avatars/userdefault.jpg', 1, NULL, '2026-01-06 03:34:44', '2025-12-29 16:52:24', '2026-01-06 03:34:44'),
(28, 'Miguel Rojas Romero', 'miguel.rojas@stratospherecorp.com', 1, NULL, '$2y$12$RRDgVk4HQEFOXL1/omNz8OppgK8Dr1OthScClCz9lwQgtgazPFm/e', 'https://fastrdjgttfnqkggxhmu.supabase.co/storage/v1/object/public/Avatars/userdefault.jpg', 1, NULL, '2025-12-30 18:25:24', '2025-12-30 04:09:51', '2025-12-30 18:25:24'),
(29, 'Miguel Segundo Sebastián', 'miguel.segundo@stratospherecorp.com', 1, NULL, '$2y$12$3D7lhuU9YiYDHaI4NLS68.T84o2fm3XHXNJ604pGPFHkdBH9wmW6C', 'https://fastrdjgttfnqkggxhmu.supabase.co/storage/v1/object/public/Avatars/userdefault.jpg', 1, NULL, NULL, '2025-12-30 04:10:38', '2025-12-30 04:10:38');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `app_service`
--
ALTER TABLE `app_service`
  ADD PRIMARY KEY (`id`);

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
  ADD PRIMARY KEY (`id`);

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
  ADD UNIQUE KEY `users_email_unique` (`email`);

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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT de la tabla `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de la tabla `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

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
-- AUTO_INCREMENT de la tabla `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

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
