<?php
include 'config.php';

// Asegurar que solo el Administrador o Editor puedan acceder a esta lógica
verificar_sesion();
global $ROLES_APROBADORES;
if (!in_array($_SESSION['usuario_rol'], $ROLES_APROBADORES)) {
    die("Acceso denegado. Solo roles aprobadores pueden gestionar publicaciones.");
}

// Configuración de conexión (Ajustar según tu entorno)
$host = 'localhost'; $user = ''; $password = ''; $db = '';

$conn = new mysqli($host, $user, $password, $db);
if ($conn->connect_error) { die("Conexión fallida: " . $conn->connect_error); }

$publicacion_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$nuevo_estado = isset($_GET['estado']) ? $_GET['estado'] : '';

// Validar ID y estado
if ($publicacion_id > 0 && in_array($nuevo_estado, ['aprobada', 'rechazada'])) {
    
    // Consulta preparada para mayor seguridad
    $sql = "UPDATE galeria_publicaciones SET estado = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $nuevo_estado, $publicacion_id);
    
    if ($stmt->execute()) {
        // Redirigir de vuelta al panel con un mensaje de éxito
        header("Location: admin.php?status=approved&pub_id={$publicacion_id}&new_state={$nuevo_estado}");
        exit();
    } else {
        die("Error al actualizar el estado: " . $conn->error);
    }
} else {
    header("Location: admin.php?error=invalid_approval_request");
    exit();
}
$conn->close();

?>
