<?php
include 'config.php'; // Incluimos la configuración y la sesión

// Configuración de conexión (Ajustar según tu entorno)
$host = 'localhost'; $user = ''; $password = ''; $db = '';
$conn = new mysqli($host, $user, $password, $db);
if ($conn->connect_error) { die("Conexión fallida: " . $conn->connect_error); }

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input_usuario = $conn->real_escape_string($_POST['usuario']);
    $input_password = $_POST['password'];

    // Buscar usuario
    $sql = "SELECT id, usuario, password_hash, rol FROM galeria_usuarios WHERE usuario = '$input_usuario'";
    $resultado = $conn->query($sql);

    if ($resultado->num_rows === 1) {
        $usuario = $resultado->fetch_assoc();
        
        // Verificar la contraseña cifrada
        if (password_verify($input_password, $usuario['password_hash'])) {
            // Éxito: Establece las variables de sesión
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_rol'] = $usuario['rol'];
            $_SESSION['usuario_nombre'] = $usuario['usuario']; // Guardamos el nombre
            
            // Redirige al panel
            header('Location: admin.php');
            exit();
        } else {
            $mensaje = "Contraseña incorrecta.";
        }
    } else {
        $mensaje = "Usuario no encontrado.";
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar Sesión</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding-top: 50px; background-color: #f7f9fc; }
        .login-box { max-width: 350px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background-color: white; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        input[type="text"], input[type="password"] { width: 90%; padding: 10px; margin: 8px 0; border: 1px solid #ccc; border-radius: 4px; }
        button { background-color: #004d99; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="login-box">
        <h1>🔐 Acceso al Panel</h1>
        <p style="color: red;"><?php echo $mensaje; ?></p>
        
        <form method="POST" action="login.php">
            <label for="usuario">Usuario:</label>
            <input type="text" name="usuario" id="usuario" required><br>
            
            <label for="password">Contraseña:</label>
            <input type="password" name="password" id="password" required><br><br>
            
            <button type="submit">Entrar</button>
        </form>
    </div>
</body>

</html>
