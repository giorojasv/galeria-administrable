<?php
include 'config.php';
verificar_sesion(); 

// Verificar que solo Editores/Administradores puedan acceder
$rol_actual = $_SESSION['usuario_rol'];
global $ROLES_APROBADORES;
if (!in_array($rol_actual, $ROLES_APROBADORES)) {
    header('Location: admin.php'); // Redirigir si no tiene el rol adecuado
    exit();
}

// Configuración de conexión (Ajustar según tu entorno)
$host = 'localhost'; $user = ''; $password = ''; $db = '';
$conn = new mysqli($host, $user, $password, $db);
if ($conn->connect_error) { die("Conexión fallida: " . $conn->connect_error); }

$publicacion_id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
$mensaje = '';

if ($publicacion_id === 0) {
    header('Location: admin.php');
    exit();
}

// -----------------------------------------------------
// LÓGICA DE PROCESAMIENTO
// -----------------------------------------------------

// 1. Manejar Eliminación de Archivo (se hace por GET para la URL limpia)
if (isset($_GET['delete_file_id'])) {
    $file_id = (int)$_GET['delete_file_id'];
    
    // Buscar el nombre del archivo para eliminarlo físicamente
    $sql_find_file = "SELECT nombre_archivo FROM galeria_archivos WHERE id = $file_id AND publicacion_id = $publicacion_id";
    $result_file = $conn->query($sql_find_file);
    
    if ($result_file->num_rows > 0) {
        $file_data = $result_file->fetch_assoc();
        $file_path = "archivos_galeria/" . $file_data['nombre_archivo'];
        
        // Eliminar el registro de la DB
        $sql_delete_db = "DELETE FROM galeria_archivos WHERE id = $file_id";
        if ($conn->query($sql_delete_db) === TRUE) {
            // Eliminar el archivo físico del servidor
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            $mensaje = "✅ Archivo eliminado con éxito.";
        } else {
            $mensaje = "❌ Error al eliminar el registro de la base de datos.";
        }
    } else {
        $mensaje = "❌ Archivo no encontrado o no pertenece a esta publicación.";
    }
}

// 2. Manejar Actualización de Descripción (POST request)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['descripcion'])) {
    $nueva_descripcion = $conn->real_escape_string($_POST['descripcion']);
    
    $sql_update = "UPDATE galeria_publicaciones SET descripcion = '{$nueva_descripcion}' WHERE id = {$publicacion_id}";
    
    if ($conn->query($sql_update) === TRUE) {
        $mensaje = "✅ Descripción actualizada con éxito.";
    } else {
        $mensaje = "❌ Error al actualizar la descripción: " . $conn->error;
    }
}


// -----------------------------------------------------
// LÓGICA DE CARGA DE DATOS (Despuéz de procesar cambios)
// -----------------------------------------------------

// 1. Cargar datos de la publicación
$sql_publicacion = "
    SELECT p.id, p.descripcion, p.fecha_publicacion, p.estado, u.usuario AS autor 
    FROM galeria_publicaciones p
    JOIN galeria_usuarios u ON p.usuario_id = u.id
    WHERE p.id = {$publicacion_id} AND p.estado = 'pendiente'
";
$resultado_pub = $conn->query($sql_publicacion);

if ($resultado_pub->num_rows === 0) {
    echo "<p style='color: red;'>Publicación no encontrada o ya ha sido aprobada/rechazada.</p>";
    $conn->close();
    exit();
}

$publicacion = $resultado_pub->fetch_assoc();

// 2. Cargar archivos asociados
$sql_archivos = "SELECT id, nombre_archivo, tipo_contenido FROM galeria_archivos WHERE publicacion_id = {$publicacion_id}";
$resultado_archivos = $conn->query($sql_archivos);

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>Editar Publicación Pendiente #<?php echo $publicacion_id; ?></title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background-color: #f4f7f9; }
        .contenedor { max-width: 800px; margin: 0 auto; background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); }
        h1 { color: #004d99; border-bottom: 2px solid #004d99; padding-bottom: 10px; }
        textarea { width: 100%; box-sizing: border-box; padding: 10px; border-radius: 4px; border: 1px solid #ccc; min-height: 150px; margin-bottom: 20px; resize: vertical; }
        button { background-color: #007bff; color: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer; }
        button:hover { background-color: #0056b3; }
        .archivo-item { display: flex; justify-content: space-between; align-items: center; padding: 10px; border: 1px solid #ddd; margin-bottom: 8px; border-radius: 4px; }
        .archivo-item button { background-color: #dc3545; padding: 5px 10px; font-size: 0.8em; }
        .mensaje-exito { background-color: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #c3e6cb; }
        .preview-mini { max-width: 60px; max-height: 60px; margin-right: 15px; object-fit: cover; border-radius: 3px; }
        .archivo-info { display: flex; align-items: center; }
    </style>
</head>
<body>

    <div class="contenedor">
        <h1>Editar Publicación Pendiente #<?php echo $publicacion['id']; ?></h1>
        
        <p>Autor: <strong><?php echo htmlspecialchars($publicacion['autor']); ?></strong> | Fecha de subida: <?php echo date("d/m/Y H:i", strtotime($publicacion['fecha_publicacion'])); ?></p>
        
        <?php if ($mensaje): ?>
            <div class="mensaje-exito"><?php echo $mensaje; ?></div>
        <?php endif; ?>

        <a href="admin.php" style="margin-bottom: 20px; display: inline-block; color: #004d99; text-decoration: none;">&larr; Volver al Panel de Administración</a>

        <h2>1. Editar Descripción</h2>
        <form method="POST" action="editar_publicacion.php?id=<?php echo $publicacion_id; ?>">
            <input type="hidden" name="id" value="<?php echo $publicacion_id; ?>">
            <label for="descripcion">Descripción actual:</label>
            <textarea name="descripcion" id="descripcion" required><?php echo htmlspecialchars($publicacion['descripcion']); ?></textarea>
            <button type="submit">Guardar Descripción</button>
        </form>

        <hr style="margin-top: 30px; margin-bottom: 30px;">
        
        <h2>2. Gestionar Archivos (<?php echo $resultado_archivos->num_rows; ?> archivos)</h2>
        <p>Puede eliminar un archivo si no es apropiado. La publicación será aprobada con los archivos restantes.</p>

        <?php if ($resultado_archivos->num_rows > 0): ?>
            <?php while ($archivo = $resultado_archivos->fetch_assoc()): ?>
                <?php $ruta = "archivos_galeria/" . $archivo['nombre_archivo']; ?>
                <div class="archivo-item">
                    <div class="archivo-info">
                        <?php if ($archivo['tipo_contenido'] === 'imagen'): ?>
                            <img src="<?php echo $ruta; ?>" class="preview-mini" alt="Preview">
                        <?php else: ?>
                            <video src="<?php echo $ruta; ?>" class="preview-mini"></video>
                        <?php endif; ?>
                        
                        <span>
                            <?php echo htmlspecialchars($archivo['nombre_archivo']); ?> 
                            (<?php echo strtoupper($archivo['tipo_contenido']); ?>)
                        </span>
                    </div>

                    <button onclick="confirmarEliminacionArchivo(<?php echo $publicacion_id; ?>, <?php echo $archivo['id']; ?>)">
                        Eliminar Archivo
                    </button>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>⚠️ Advertencia: Esta publicación no tiene archivos asociados. Si la aprueba, estará vacía.</p>
        <?php endif; ?>

    </div>

    <script>
        function confirmarEliminacionArchivo(publicacionId, archivoId) {
            if (confirm("⚠️ ¿Estás SEGURO de que quieres eliminar este archivo? Esta acción es permanente y puede dejar la publicación vacía.")) {
                window.location.href = `editar_publicacion.php?id=${publicacionId}&delete_file_id=${archivoId}`;
            }
        }
    </script>
</body>

</html>
