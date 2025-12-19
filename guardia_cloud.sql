-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 19-12-2025 a las 01:53:30
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
(2, 'Infraestructura Stratosphere', 1, '2025-12-19 00:52:15', '2025-12-19 00:52:15');

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
(21, '2025_12_18_111630_create_areas_table', 2);

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
(4, 'App\\Models\\User', 16),
(9, 'App\\Models\\User', 13),
(9, 'App\\Models\\User', 14),
(9, 'App\\Models\\User', 18),
(9, 'App\\Models\\User', 19),
(9, 'App\\Models\\User', 20);

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
  `description` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `guard_name`, `description`, `created_at`, `updated_at`) VALUES
(7, 'users.export', 'api', 'Permite exportar usuarios', '2025-12-08 18:16:25', '2025-12-08 18:16:25'),
(8, 'users.browse', 'api', 'Método que permite navegar en la sección users.', '2025-12-08 18:27:27', '2025-12-08 18:27:27'),
(9, 'users.create', 'api', 'Método que permite visualizar la sección para crear un users.', '2025-12-08 18:27:27', '2025-12-08 18:27:27'),
(10, 'users.edit', 'api', 'Método que permite visualizar la sección para editar un users.', '2025-12-08 18:27:27', '2025-12-08 18:27:27'),
(11, 'users.delete', 'api', 'Método que permite eliminar un users.', '2025-12-08 18:27:27', '2025-12-08 18:27:27'),
(12, 'users.show', 'api', 'Método que permite visualizar los detalles de un users.', '2025-12-08 18:27:27', '2025-12-08 18:27:27'),
(13, 'console.browse', 'api', 'Método que permite navegar en la sección console.', '2025-12-08 19:23:53', '2025-12-08 19:23:53'),
(20, 'permisos.browse', 'api', 'Método que permite navegar en la sección permisos.', '2025-12-11 00:51:31', '2025-12-11 00:51:31'),
(21, 'permisos.create', 'api', 'Método que permite visualizar la sección para crear un permisos.', '2025-12-11 00:51:31', '2025-12-11 00:51:31'),
(22, 'permisos.edit', 'api', 'Método que permite visualizar la sección para editar un permisos.', '2025-12-11 00:51:31', '2025-12-11 00:51:31'),
(23, 'permisos.delete', 'api', 'Método que permite eliminar un permisos.', '2025-12-11 00:51:31', '2025-12-11 00:51:31'),
(25, 'roles.browse', 'api', 'Método que permite navegar en la sección roles.', '2025-12-11 17:52:15', '2025-12-11 17:52:15'),
(26, 'roles.create', 'api', 'Método que permite visualizar la sección para crear un roles.', '2025-12-11 17:52:15', '2025-12-11 17:52:15'),
(27, 'roles.edit', 'api', 'Método que permite visualizar la sección para editar un roles.', '2025-12-11 17:52:15', '2025-12-11 17:52:15'),
(28, 'roles.delete', 'api', 'Método que permite eliminar un roles.', '2025-12-11 17:52:15', '2025-12-11 17:52:15'),
(29, 'roles.show', 'api', 'Método que permite visualizar los detalles de un roles.', '2025-12-11 17:52:15', '2025-12-11 17:52:15'),
(34, 'users.stats', 'api', 'Permiso para ver las estadisticas', '2025-12-18 04:23:33', '2025-12-18 04:23:33'),
(35, 'area.browse', 'api', 'Método que permite navegar en la sección area.', '2025-12-18 17:47:26', '2025-12-18 17:47:26'),
(36, 'area.create', 'api', 'Método que permite visualizar la sección para crear un area.', '2025-12-18 17:47:26', '2025-12-18 17:47:26'),
(37, 'area.edit', 'api', 'Método que permite visualizar la sección para editar un area.', '2025-12-18 17:47:26', '2025-12-18 17:47:26'),
(38, 'area.delete', 'api', 'Método que permite eliminar un area.', '2025-12-18 17:47:26', '2025-12-18 17:47:26'),
(39, 'area.show', 'api', 'Método que permite visualizar los detalles de un area.', '2025-12-18 17:47:26', '2025-12-18 17:47:26');

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
(9, 'Cloud Services Support', 'api', '2025-12-11 20:37:43', '2025-12-11 20:37:43');

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
(8, 4),
(8, 9),
(9, 4),
(9, 9),
(10, 4),
(10, 9),
(11, 4),
(11, 9),
(12, 4),
(12, 9),
(13, 4),
(13, 7),
(20, 4),
(21, 4),
(22, 4),
(23, 4),
(25, 4),
(25, 9),
(26, 4),
(26, 9),
(27, 4),
(27, 9),
(28, 4),
(28, 9),
(29, 4),
(29, 9),
(34, 4),
(35, 4),
(35, 9),
(36, 4),
(36, 9),
(37, 4),
(37, 9),
(38, 4),
(38, 9),
(39, 4),
(39, 9);

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
(12, 'Alfredo Villavicencio Luis', 'avillavicencio@teamnet.com.mx', NULL, NULL, '$2y$12$tMJUclT3xI2HQfYCwgaIgOF97hGtqoc.u3w4acTJC17Bh5zp/QV0K', 'https://fastrdjgttfnqkggxhmu.supabase.co/storage/v1/object/public/Avatars/12-1765918678281.webp', 1, NULL, '2025-12-19 00:53:06', '2025-12-05 06:43:48', '2025-12-19 00:53:06'),
(13, 'Gustavo Pruebas', 'gustavo.pruebas@example.com', NULL, NULL, '$2y$12$.bkbhE.Qft1gUhJXI3uJRu7edlVOgvJFwTRCSPoO9rbxbVv06qMGG', 'https://fastrdjgttfnqkggxhmu.supabase.co/storage/v1/object/public/Avatars/userdefault.jpg', 1, NULL, NULL, '2025-12-05 22:08:27', '2025-12-17 16:50:00'),
(14, 'Marianne Hodkiewicz', 'correo.cambiado@empresa.com', NULL, '2025-12-05 22:10:14', '$2y$12$kHAZgFhwJlTIRw7rkyWnkek1aTp9Ye3cmMlNKPE3U0r0pmh/BW6Ce', 'https://fastrdjgttfnqkggxhmu.supabase.co/storage/v1/object/public/Avatars/userdefault.jpg', 0, 'QUheGeImvq', NULL, '2025-12-05 22:10:14', '2025-12-18 05:34:29'),
(16, 'Bernardo Jast', 'rolando.durgan@example.net', NULL, '2025-12-05 22:10:14', '$2y$12$GeIP67KWH9oIQiAGgEk6zeLaNRA4YpIATdUHWj3FImhcsmMheJ4Rm', 'https://fastrdjgttfnqkggxhmu.supabase.co/storage/v1/object/public/Avatars/userdefault.jpg', 1, 'brfsLoHBaW', '2025-12-18 01:21:13', '2025-12-05 22:10:14', '2025-12-18 01:21:13'),
(18, 'Dr Kyler Dare', 'andreane70@example.com', NULL, '2025-12-05 22:10:14', '$2y$12$BOoqe4yulQRy.Q/aQf0mSOwjWdZhTRt.K.3SZ/sAMW0nEuKeZCcTa', 'https://fastrdjgttfnqkggxhmu.supabase.co/storage/v1/object/public/Avatars/userdefault.jpg', 1, 'UfhqQPexre', NULL, '2025-12-05 22:10:14', '2025-12-17 23:18:43'),
(19, 'Alfredo Villavicencio', 'alfre1230999@gmail.com', NULL, NULL, '$2y$12$Asl6VVhEKD3YVgyXQqap1e4aF01L4EpA7X/u00WAmk78Cq14p4Ynq', 'https://fastrdjgttfnqkggxhmu.supabase.co/storage/v1/object/public/Avatars/19-1765600333918.webp', 1, NULL, '2025-12-18 23:48:58', '2025-12-13 04:03:40', '2025-12-18 23:48:58'),
(20, 'Juan Perez', 'juan.perez+test1@empresa.com', NULL, NULL, '$2y$12$AxiSEH4oni/oEE2t0x7xM.ZaL4etG..Kt5P8/UriCGo0IE3u//chi', 'https://fastrdjgttfnqkggxhmu.supabase.co/storage/v1/object/public/Avatars/userdefault.jpg', 1, NULL, NULL, '2025-12-16 01:57:35', '2025-12-16 01:57:35');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `areas`
--
ALTER TABLE `areas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `areas_name_unique` (`name`);

--
-- Indices de la tabla `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

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
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `areas`
--
ALTER TABLE `areas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de la tabla `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT de la tabla `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

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
