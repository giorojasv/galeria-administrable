<?php
include 'config.php';
verificar_rol_admin(); // Solo el Admin puede crear nuevos usuarios

// Configuración de conexión (Ajustar según tu entorno)
$host = 'localhost'; $user = ''; $password = ''; $db = '';

$conn = new mysqli($host, $user, $password, $db);
if ($conn->connect_error) { die("Conexión fallida: " . $conn->connect_error); }

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario']);
    $clave = $_POST['clave'];
    $rol = $_POST['rol'];

    // Validación básica de campos
    if (empty($usuario) || empty($clave) || empty($rol)) {
        $mensaje = "<p class='error'>❌ Todos los campos son obligatorios.</p>";
    } elseif (strlen($clave) < 6) {
        $mensaje = "<p class='error'>❌ La contraseña debe tener al menos 6 caracteres.</p>";
    } elseif (!in_array($rol, [ROL_ADMIN, ROL_EDITOR, ROL_COLABORADOR])) { // Validación de rol
        $mensaje = "<p class='error'>❌ Rol de usuario inválido.</p>";
    } else {
        // Cifrado seguro de la contraseña
        $password_hash = password_hash($clave, PASSWORD_DEFAULT);

        // Verificar si el usuario ya existe
        $sql_check = "SELECT id FROM galeria_usuarios WHERE usuario = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("s", $usuario);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $mensaje = "<p class='error'>❌ El nombre de usuario ya existe.</p>";
        } else {
            // Insertar nuevo usuario
            $sql_insert = "INSERT INTO galeria_usuarios (usuario, password_hash, rol) VALUES (?, ?, ?)";
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("sss", $usuario, $password_hash, $rol);

            if ($stmt_insert->execute()) {
                $mensaje = "<p class='exito'>✅ Usuario **" . htmlspecialchars($usuario) . "** creado con el rol: **" . htmlspecialchars($rol) . "**.</p>";
            } else {
                $mensaje = "<p class='error'>❌ Error al crear el usuario: " . $stmt_insert->error . "</p>";
            }
            $stmt_insert->close();
        }
        $stmt_check->close();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Usuario | Admin</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background-color: #f7f9fc; }
        .contenedor { max-width: 400px; margin: 0 auto; padding: 30px; background-color: white; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        h1 { color: #004d99; text-align: center; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="password"], select {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 10px;
            background-color: #004d99;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s;
        }
        button:hover { background-color: #003366; }
        .exito { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
    </style>
</head>
<body>
    <div class="contenedor">
        <h1>➕ Crear Cuenta de Usuario</h1>
        
        <?php echo $mensaje; ?>
        
        <form action="registro_usuario.php" method="POST">
            <label for="usuario">Nombre de Usuario:</label>
            <input type="text" id="usuario" name="usuario" required>

            <label for="clave">Contraseña (Mín. 6 caracteres):</label>
            <input type="password" id="clave" name="clave" required minlength="6">

            <label for="rol">Rol:</label>
            <select id="rol" name="rol" required>
                <option value="<?php echo ROL_ADMIN; ?>">Administrador</option>
                <option value="<?php echo ROL_EDITOR; ?>">Editor</option>
                <option value="<?php echo ROL_COLABORADOR; ?>">Colaborador</option> </select>

            <button type="submit">Registrar Usuario</button>
        </form>
        
        <p style="text-align: center; margin-top: 20px;"><a href="admin.php">← Volver al Panel</a></p>
    </div>
</body>

</html>
