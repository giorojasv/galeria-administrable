<?php
include 'config.php';
verificar_rol_admin(); // ¡SOLO ADMINISTRADORES PUEDEN PASAR DE ESTE PUNTO!

// Configuración de conexión (Ajustar según tu entorno)
$host = 'localhost'; $user = 'academia_galeria'; $password = '@galeria2025'; $db = 'academia_galeria';

$conn = new mysqli($host, $user, $password, $db);
if ($conn->connect_error) { die("Conexión fallida: " . $conn->connect_error); }

$directorio_subidas = 'archivos_galeria/';

// 1. Recibir y validar el ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID de publicación no válido.");
}
$publicacion_id = (int)$_GET['id'];

// 2. Transacción: Evitar inconsistencias
$conn->begin_transaction();

try {
    // A. OBTENER RUTAS DE ARCHIVOS FÍSICOS
    $sql_archivos = "SELECT nombre_archivo FROM galeria_archivos WHERE publicacion_id = $publicacion_id";
    $resultado = $conn->query($sql_archivos);
    
    $archivos_a_borrar = [];
    if ($resultado->num_rows > 0) {
        while ($fila = $resultado->fetch_assoc()) {
            $archivos_a_borrar[] = $directorio_subidas . $fila['nombre_archivo'];
        }
    }
    
    // B. ELIMINAR REGISTROS DE LA BASE DE DATOS
    // Se elimina la publicación principal y los archivos asociados (si usaste ON DELETE CASCADE)
    $sql_eliminar_pub = "DELETE FROM galeria_publicaciones WHERE id = $publicacion_id";
    if (!$conn->query($sql_eliminar_pub)) {
        throw new Exception("Error al eliminar la publicación de la BD.");
    }
    
    // C. ELIMINAR ARCHIVOS FÍSICOS DEL SERVIDOR
    foreach ($archivos_a_borrar as $ruta_archivo) {
        if (file_exists($ruta_archivo)) {
            unlink($ruta_archivo);
        }
    }

    // 3. Confirmar la transacción
    $conn->commit();
    
    // 4. Redirigir de vuelta a la página de administración
    header("Location: admin.php?status=deleted");
    exit();

} catch (Exception $e) {
    $conn->rollback();
    die("Error al intentar eliminar la publicación: " . $e->getMessage());
}
$conn->close();
?>