<?php
include 'config.php';
verificar_sesion(); 

// Obtener el ID y ROL del usuario logueado
$usuario_id_actual = $_SESSION['usuario_id'];
$rol_usuario_actual = $_SESSION['usuario_rol'];
global $ROLES_APROBADORES; // Para usar la lista de roles aprobadores

// Configuración de conexión (Ajustar según tu entorno)
$host = 'localhost'; $user = 'academia_galeria'; $password = '@galeria2025'; $db = 'academia_galeria';

$conn = new mysqli($host, $user, $password, $db);
if ($conn->connect_error) { die("Conexión fallida: " . $conn->connect_error); }

$directorio_subidas = 'archivos_galeria/';
if (!is_dir($directorio_subidas)) { mkdir($directorio_subidas, 0777, true); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $descripcion = $conn->real_escape_string($_POST['descripcion']);
    
    $conn->begin_transaction();
    $archivos_subidos = []; 

    try {
        // Determinar el estado inicial: Aprobada si es Admin/Editor, Pendiente si es Colaborador
        $estado_inicial = in_array($rol_usuario_actual, $ROLES_APROBADORES) ? 'aprobada' : 'pendiente';
        
        // PASO 1: Insertar el registro principal del COLLAGE/PUBLICACIÓN 
        // Incluimos usuario_id y estado
        $sql_pub = "INSERT INTO galeria_publicaciones (descripcion, usuario_id, estado) VALUES (?, ?, ?)";
        
        $stmt_pub = $conn->prepare($sql_pub);
        $stmt_pub->bind_param("sis", $descripcion, $usuario_id_actual, $estado_inicial); 
        
        if (!$stmt_pub->execute()) {
            throw new Exception("Error al crear la publicación en BD: " . $stmt_pub->error);
        }
        $publicacion_id = $conn->insert_id;
        $stmt_pub->close();


        // PASO 2: Procesar todos los archivos subidos
        foreach ($_FILES['archivos']['name'] as $clave => $nombre_archivo) {
            $tmp_name = $_FILES['archivos']['tmp_name'][$clave];
            $error = $_FILES['archivos']['error'][$clave];

            if ($error !== UPLOAD_ERR_OK) { continue; }

            $mime_type = mime_content_type($tmp_name);
            $extension = strtolower(pathinfo($nombre_archivo, PATHINFO_EXTENSION));
            
            // LÓGICA DE DETECCIÓN DE TIPO (videos y mp4)
            if (strpos($mime_type, 'image/') === 0) { 
                $tipo_contenido = 'imagen'; 
            } 
            elseif (strpos($mime_type, 'video/') === 0 || in_array($extension, ['mp4', 'mov', 'webm'])) { 
                $tipo_contenido = 'video'; 
            } 
            else { 
                continue; 
            }

            $nombre_unico = time() . uniqid() . '.' . $extension;
            $ruta_final = $directorio_subidas . $nombre_unico;

            if (!move_uploaded_file($tmp_name, $ruta_final)) {
                throw new Exception("Error al mover el archivo al directorio.");
            }
            $archivos_subidos[] = $ruta_final; 

            // Insertar registro de archivo
            $sql_archivo = "INSERT INTO galeria_archivos (publicacion_id, nombre_archivo, tipo_contenido) VALUES (?, ?, ?)";
            $stmt_arc = $conn->prepare($sql_archivo);
            $stmt_arc->bind_param("iss", $publicacion_id, $nombre_unico, $tipo_contenido);
            
            if (!$stmt_arc->execute()) {
                throw new Exception("Error al registrar archivo en BD.");
            }
            $stmt_arc->close();
        }
        
        // Si todo salió bien, confirmar la transacción
        $conn->commit();
        
        $descripcion_html = htmlspecialchars($descripcion);
        
        // ... (HTML de éxito) ...
        echo <<<HTML
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <title>Subida Exitosa</title>
            <style>
                body { font-family: Arial, sans-serif; text-align: center; padding-top: 50px; background-color: #f4f7f6; }
                .contenedor { max-width: 600px; margin: 0 auto; padding: 30px; border: 1px solid #4CAF50; border-radius: 8px; background-color: white; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
                h2 { color: #4CAF50; }
                .nota { font-style: italic; color: #dc3545; font-weight: bold;}
                .boton-galeria { 
                    display: inline-block; padding: 12px 25px; margin-top: 20px;
                    background-color: #4CAF50; color: white; text-decoration: none; 
                    border-radius: 50px; font-size: 1.1em; font-weight: bold;
                    transition: background-color 0.3s;
                }
                .boton-galeria:hover { background-color: #45a049; }
            </style>
        </head>
        <body>
            <div class="contenedor">
                <h2>¡Collage Creado con Éxito! 🎉</h2>
                <p>Descripción: <strong>{$descripcion_html}</strong></p>
                
                <p class="nota">Estado: <strong>{$estado_inicial}</strong></p>
                
                <p>Si la publicación está pendiente, un Administrador o Editor deberá aprobarla para que sea visible públicamente.</p>
                <a href="index_publica.php" class="boton-galeria">
                    Ver Galería Pública
                </a>
                <p style="margin-top: 25px;"><small><a href="admin.php">Subir otro collage</a></small></p>
            </div>
        </body>
        </html>
HTML;

    } catch (Exception $e) {
        $conn->rollback();
        foreach ($archivos_subidos as $ruta) {
            if (file_exists($ruta)) { unlink($ruta); }
        }
        die("Error en la subida del collage: " . $e->getMessage() . ". Se deshicieron los cambios.");
    }
}
$conn->close();
?>