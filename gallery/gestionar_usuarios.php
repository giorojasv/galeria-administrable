<?php
include 'config.php';
verificar_rol_admin(); // Solo el Administrador puede acceder

// Configuración de conexión (Ajustar según tu entorno)
$host = 'localhost'; $user = ''; $password = ''; $db = '';

$conn = new mysqli($host, $user, $password, $db);
if ($conn->connect_error) { die("Conexión fallida: " . $conn->connect_error); }

$mensaje = "";

// --- LÓGICA DE GESTIÓN DE ACCIONES (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'];
    $usuario_id = intval($_POST['usuario_id']);
    
    // 1. ELIMINAR USUARIO
    if ($accion === 'eliminar' && $usuario_id > 0) {
        // Validación de seguridad: No permitir que el admin se elimine a sí mismo
        if ($usuario_id == $_SESSION['usuario_id']) {
            $mensaje = "<p class='error'>❌ No puedes eliminar tu propia cuenta mientras estás logueado.</p>";
        } else {
            $sql_del = "DELETE FROM galeria_usuarios WHERE id = $usuario_id";
            if ($conn->query($sql_del) === TRUE && $conn->affected_rows > 0) {
                $mensaje = "<p class='exito'>✅ Usuario con ID {$usuario_id} eliminado correctamente.</p>";
            } else {
                $mensaje = "<p class='error'>❌ Error al eliminar el usuario o no existe.</p>";
            }
        }
    }
    
    // 2. CAMBIAR CONTRASEÑA
    if ($accion === 'cambiar_clave' && $usuario_id > 0) {
        $nueva_clave = $_POST['nueva_clave'];
        if (strlen($nueva_clave) < 6) {
             $mensaje = "<p class='error'>❌ La nueva contraseña debe tener al menos 6 caracteres.</p>";
        } else {
            $password_hash = password_hash($nueva_clave, PASSWORD_DEFAULT);
            $sql_update = "UPDATE galeria_usuarios SET password_hash = ? WHERE id = ?";
            
            $stmt = $conn->prepare($sql_update);
            $stmt->bind_param("si", $password_hash, $usuario_id);
            
            if ($stmt->execute()) {
                $mensaje = "<p class='exito'>🔑 Contraseña del usuario ID {$usuario_id} actualizada con éxito.</p>";
            } else {
                $mensaje = "<p class='error'>❌ Error al actualizar la contraseña: " . $stmt->error . "</p>";
            }
            $stmt->close();
        }
    }
}

// --- LISTADO DE USUARIOS ---
$sql_list = "SELECT id, usuario, rol FROM galeria_usuarios ORDER BY id ASC";
$resultado = $conn->query($sql_list);
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios | Admin</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background-color: #f7f9fc; }
        .contenedor { max-width: 900px; margin: 0 auto; padding: 30px; background-color: white; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        h1 { color: #004d99; }
        .exito { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .lista-usuarios { list-style: none; padding: 0; }
        .usuario-item { 
            border: 1px solid #ddd; 
            padding: 15px; 
            margin-bottom: 10px; 
            border-radius: 5px; 
            display: flex; 
            flex-wrap: wrap; /* Permite que los elementos se envuelvan */
            justify-content: space-between; 
            align-items: center; 
        }
        .usuario-info { margin-right: 20px; }
        .acciones { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .btn-eliminar { background-color: #dc3545; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; }
        .btn-cambiar { background-color: #ffc107; color: #333; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; }
        input[type="password"] { padding: 8px; border: 1px solid #ccc; border-radius: 4px; width: 150px; }
        .clave-form { display: flex; align-items: center; gap: 5px; }

        @media (max-width: 768px) {
            .usuario-item { flex-direction: column; align-items: flex-start; }
            .usuario-info { margin-bottom: 10px; }
            .acciones { width: 100%; justify-content: space-between; }
            .clave-form { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>
    <div class="contenedor">
        <h1>🛠️ Gestión de Usuarios de la Galería</h1>
        
        <?php echo $mensaje; ?>
        
        <p><a href="admin.php">← Volver al Panel Principal</a> | 
           <a href="registro_usuario.php">Crear Nuevo Usuario [+]</a>
        </p>

        <ul class="lista-usuarios">
            <?php if ($resultado->num_rows > 0): ?>
                <?php while ($user = $resultado->fetch_assoc()): ?>
                    <li class="usuario-item">
                        <div class="usuario-info">
                            <strong>ID: <?php echo $user['id']; ?> | Usuario: <?php echo htmlspecialchars($user['usuario']); ?></strong> 
                            (Rol: **<?php echo htmlspecialchars($user['rol']); ?>**)
                        </div>
                        
                        <div class="acciones">
                            <form action="gestionar_usuarios.php" method="POST" class="clave-form" onsubmit="return confirmarCambioClave('<?php echo htmlspecialchars($user['usuario']); ?>')">
                                <input type="hidden" name="accion" value="cambiar_clave">
                                <input type="hidden" name="usuario_id" value="<?php echo $user['id']; ?>">
                                <input type="password" name="nueva_clave" placeholder="Nueva clave" required minlength="6">
                                <button type="submit" class="btn-cambiar">Cambiar Clave</button>
                            </form>
                            
                            <form action="gestionar_usuarios.php" method="POST" onsubmit="return confirmarEliminar('<?php echo htmlspecialchars($user['usuario']); ?>')">
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="usuario_id" value="<?php echo $user['id']; ?>">
                                <button type="submit" class="btn-eliminar" <?php echo ($user['id'] == $_SESSION['usuario_id'] ? 'disabled' : ''); ?>>
                                    Eliminar
                                </button>
                            </form>
                        </div>
                    </li>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No hay usuarios registrados.</p>
            <?php endif; ?>
        </ul>
    </div>

    <script>
        function confirmarEliminar(usuario) {
            return confirm(`⚠️ ¿Estás seguro de que quieres eliminar al usuario ${usuario}? Esta acción no se puede deshacer.`);
        }
        function confirmarCambioClave(usuario) {
            return confirm(`🔑 ¿Confirmas que deseas cambiar la contraseña del usuario ${usuario}?`);
        }
    </script>
</body>

</html>
