<?php
// Inicia la sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Constantes de Roles
define('ROL_ADMIN', 'administrador');
define('ROL_EDITOR', 'editor');
define('ROL_COLABORADOR', 'colaborador'); // <-- ¡NUEVO ROL!

// Roles que tienen permiso para APROBAR publicaciones (Admin y Editor)
$ROLES_APROBADORES = [ROL_ADMIN, ROL_EDITOR];

/**
 * Verifica si existe una sesión de usuario válida.
 * Si no hay sesión, redirige al login.
 */
function verificar_sesion() {
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: login.php');
        exit();
    }
}

/**
 * Verifica si el usuario logueado tiene el rol de administrador.
 * Si no es admin, muestra error y termina.
 */
function verificar_rol_admin() {
    verificar_sesion();
    if ($_SESSION['usuario_rol'] !== ROL_ADMIN) {
        die("Acceso denegado. Solo el administrador puede realizar esta acción.");
    }
}
?>