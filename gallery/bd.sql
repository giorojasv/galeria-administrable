-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 09-10-2025 a las 11:59:37
-- Versión del servidor: 10.6.22-MariaDB-cll-lve
-- Versión de PHP: 8.3.23

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `academia_galeria`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `galeria_archivos`
--

CREATE TABLE `galeria_archivos` (
  `id` int(11) NOT NULL,
  `publicacion_id` int(11) NOT NULL,
  `nombre_archivo` varchar(255) NOT NULL COMMENT 'Ruta del archivo',
  `tipo_contenido` enum('imagen','video') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `galeria_publicaciones`
--

CREATE TABLE `galeria_publicaciones` (
  `id` int(11) NOT NULL,
  `descripcion` text DEFAULT NULL COMMENT 'La descripción del evento o collage',
  `fecha_publicacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `usuario_id` int(11) NOT NULL,
  `estado` enum('pendiente','aprobada','rechazada') NOT NULL DEFAULT 'pendiente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `galeria_usuarios`
--

CREATE TABLE `galeria_usuarios` (
  `id` int(11) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `rol` enum('editor','administrador') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `galeria_usuarios`
--

INSERT INTO `galeria_usuarios` (`id`, `usuario`, `password_hash`, `rol`) VALUES
(1, 'admin', '$2y$12$QA9BObDEQbEj8troiJxa0.L.UvS2qmDHLnrwT6bHPSXA4G2QEy0VO', 'administrador'),
(2, 'editor', '$2y$12$jsbbgnOqdJw5nfQe3q3bCeILfVj3HkZKo1Um05edPQ3hd5ul/pIO6', 'editor'),
(3, 'editor2', '$2y$12$EkDutrr62TaO0VQqhTAhAOBVrZvbN0vKo77EjwCru1m4lJiCQToWq', 'editor'),
(4, 'ceal', '$2y$12$fkWh1Zej/EvEBpQeli68f.YJtqL85a.tEhkzfzpnxDfD/MpZ0Pa1m', '');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `galeria_archivos`
--
ALTER TABLE `galeria_archivos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `publicacion_id` (`publicacion_id`);

--
-- Indices de la tabla `galeria_publicaciones`
--
ALTER TABLE `galeria_publicaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `galeria_usuarios`
--
ALTER TABLE `galeria_usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `galeria_archivos`
--
ALTER TABLE `galeria_archivos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=85;

--
-- AUTO_INCREMENT de la tabla `galeria_publicaciones`
--
ALTER TABLE `galeria_publicaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de la tabla `galeria_usuarios`
--
ALTER TABLE `galeria_usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `galeria_archivos`
--
ALTER TABLE `galeria_archivos`
  ADD CONSTRAINT `galeria_archivos_ibfk_1` FOREIGN KEY (`publicacion_id`) REFERENCES `galeria_publicaciones` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `galeria_publicaciones`
--
ALTER TABLE `galeria_publicaciones`
  ADD CONSTRAINT `galeria_publicaciones_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `galeria_usuarios` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
