<?php
include 'config.php';
verificar_sesion(); // Obliga a iniciar sesión

// Configuración de conexión (Ajustar según tu entorno)
$host = 'localhost'; $user = ''; $password = ''; $db = '';

$conn = new mysqli($host, $user, $password, $db);
if ($conn->connect_error) { die("Conexión fallida: " . $conn->connect_error); }

// --- CONFIGURACIÓN DE PAGINACIÓN ---
$publicaciones_por_pagina = 10;
$pagina_actual = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($pagina_actual - 1) * $publicaciones_por_pagina;

// 1. Consulta para OBTENER EL NÚMERO TOTAL de publicaciones
$sql_count = "SELECT COUNT(p.id) AS total FROM galeria_publicaciones p";
$resultado_count = $conn->query($sql_count);
$total_publicaciones = $resultado_count->fetch_assoc()['total'];
$total_pages = ceil($total_publicaciones / $publicaciones_por_pagina);


// 2. Consulta para OBTENER las publicaciones de la página actual (con LIMIT y OFFSET)
$sql_listado = "
    SELECT p.id, p.descripcion, p.fecha_publicacion, p.estado, u.usuario AS autor 
    FROM galeria_publicaciones p
    JOIN galeria_usuarios u ON p.usuario_id = u.id
    ORDER BY p.fecha_publicacion DESC
    LIMIT {$publicaciones_por_pagina} OFFSET {$offset}
";
$resultado_listado = $conn->query($sql_listado);
$conn->close();

// Obtenemos el rol del usuario logueado y definimos si es aprobador
$rol_actual = $_SESSION['usuario_rol'];
global $ROLES_APROBADORES;
$es_aprobador = in_array($rol_actual, $ROLES_APROBADORES);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>Administración (Rol: <?php echo $rol_actual; ?>)</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        h1, h2 { color: #004d99; }
        
        .session-info { background-color: #e9f7ef; padding: 10px; border-left: 5px solid #4CAF50; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; }
        .session-info div { display: flex; align-items: center; margin-top: 5px; }

        form { max-width: 600px; margin-bottom: 30px; padding: 15px; border: 1px solid #ddd; border-radius: 8px; }
        input[type="file"], textarea, button { width: 100%; box-sizing: border-box; margin-top: 5px; margin-bottom: 15px; padding: 8px; border-radius: 4px; border: 1px solid #ccc; }
        textarea { resize: vertical; }
        button[type="submit"] { background-color: #004d99; color: white; border: none; cursor: pointer; }

        /* Estilos de Listado y Acciones */
        .publicacion-item { padding: 10px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 5px; display: flex; justify-content: space-between; align-items: center; flex-direction: row; }
        .publicacion-info { max-width: 70%; font-size: 0.9em; }
        .publicacion-info p { margin: 3px 0; }
        .acciones { display: flex; gap: 5px; flex-wrap: wrap; }
        .acciones a, .acciones button { padding: 6px 10px; border-radius: 4px; text-decoration: none; white-space: nowrap; border: none; cursor: pointer; }

        /* Colores por estado */
        .estado-pendiente { background-color: #fff3cd; border-color: #ffc107; }
        .estado-aprobada { background-color: #d4edda; border-color: #28a745; }
        .estado-rechazada { background-color: #f8d7da; border-color: #dc3545; }
        .boton-eliminar { background-color: #dc3545; color: white; }
        .boton-aprobar { background-color: #28a745; color: white; }
        .boton-rechazar { background-color: #ffc107; color: #333; }
        .boton-preview { background-color: #007bff; color: white; }
        
        .pagination a { padding: 8px 15px; margin: 0 4px; text-decoration: none; border-radius: 4px; font-weight: bold; }

        @media (max-width: 600px) {
            .publicacion-item { flex-direction: column; align-items: flex-start; }
            .publicacion-info { max-width: 100%; margin-bottom: 10px; }
        }
    </style>
</head>
<body>

    <div class="session-info">
        <span>Sesión iniciada como: <strong><?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></strong> (<?php echo $rol_actual; ?>)</span>
        <div>
            <?php if ($rol_actual === ROL_ADMIN): ?>
                <a href="gestionar_usuarios.php" style="margin-right: 15px; color: #dc3545; font-weight: bold;">[Usuarios]</a> 
                <a href="registro_usuario.php" style="margin-right: 15px; color: #007bff; font-weight: bold;">[+] Crear Usuario</a>
            <?php endif; ?>
            <a href="logout.php">Cerrar Sesión</a>
        </div>
    </div>

    <h1>Subir Nuevo Collage o Evento</h1>
    
    <?php if ($rol_actual === ROL_COLABORADOR): ?>
        <p style="color: #004d99; font-weight: bold; background-color: #e9f7ef; padding: 10px; border-radius: 5px;">
         **Tu rol es Colaborador.** Las publicaciones serán **PENDIENTES** hasta que un Administrador/Editor las apruebe.
        </p>
    <?php endif; ?>

    <form id="uploadForm" method="POST" enctype="multipart/form-data">
        
        <label for="archivos">1. Seleccionar Imágenes/Videos (Selecciona varios a la vez):</label><br>
        <input type="file" name="archivos[]" id="archivos" multiple required><br><br>
        
        <label for="descripcion">2. Descripción del Evento/Collage:</label><br>
        <textarea name="descripcion" id="descripcion" rows="4" cols="50" required></textarea><br><br>
        
        <button type="submit">Crear Collage y Publicar</button>
    </form>
    
    <div id="progressContainer" style="display: none; margin-top: 20px; max-width: 600px; background-color: #fff; padding: 15px; border: 1px solid #ccc; border-radius: 5px;">
        <h3>Subiendo Archivos...</h3>
        <div style="height: 30px; background-color: #e9ecef; border-radius: 4px; overflow: hidden;">
            <div id="progressBar" style="width: 0%; height: 100%; background-color: #007bff; text-align: center; line-height: 30px; color: white; font-weight: bold; transition: width 0.3s ease;">
                0%
            </div>
        </div>
        <p id="statusMessage" style="margin-top: 10px; font-size: 0.9em;">Conectando con el servidor...</p>
        <button id="cancelButton" style="width: 100%; margin-top: 10px; background-color: #6c757d;" onclick="cancelUpload()">Cancelar Subida</button>
    </div>

    <hr>
    
    <h2>Gestión de Publicaciones Existentes (Total: <?php echo $total_publicaciones; ?>)</h2>
    
    <?php if (isset($_GET['status']) && $_GET['status'] == 'deleted'): ?>
        <p style="color: green; font-weight: bold;"> Publicación eliminada con éxito.</p>
    <?php elseif (isset($_GET['status']) && $_GET['status'] == 'approved'): ?>
        <p style="color: green; font-weight: bold;"> Publicación ID <?php echo htmlspecialchars($_GET['pub_id']); ?> ha cambiado a estado: **<?php echo strtoupper(htmlspecialchars($_GET['new_state'])); ?>**.</p>
    <?php endif; ?>

    <?php if ($resultado_listado->num_rows > 0): ?>
        <?php while ($fila = $resultado_listado->fetch_assoc()): ?>
            <div class="publicacion-item estado-<?php echo $fila['estado']; ?>">
                <div class="publicacion-info">
                    <p><strong>ID:</strong> <?php echo $fila['id']; ?> | <strong>Autor:</strong> <?php echo htmlspecialchars($fila['autor']); ?></p>
                    <p><strong>Estado:</strong> <span style="font-weight: bold; text-transform: uppercase;"><?php echo $fila['estado']; ?></span></p>
                    <p><strong>Descripción:</strong> <?php echo htmlspecialchars(substr($fila['descripcion'], 0, 70)) . '...'; ?></p>
                    <p><small>Publicado el: <?php echo date("d/m/Y H:i", strtotime($fila['fecha_publicacion'])); ?></small></p>
                </div>
                
                <div class="acciones">
                    
                    <?php if ($rol_actual !== ROL_COLABORADOR): ?>
                    <a href="vista_previa.php?id=<?php echo $fila['id']; ?>" target="_blank" 
                       class="boton-preview" title="Ver como el público">
                       Vista Previa 
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($es_aprobador && $fila['estado'] === 'pendiente'): ?>
                        
                        <a href="editar_publicacion.php?id=<?php echo $fila['id']; ?>" class="boton-preview" 
                           title="Modificar descripción y archivos" style="background-color: #f0ad4e;">
                           Editar 
                        </a>
                        
                        <a href="aprobar_publicacion.php?id=<?php echo $fila['id']; ?>&estado=aprobada" 
                           onclick="return confirm('¿Aprobar esta publicación? Se hará visible en la galería.');"
                           class="boton-aprobar" title="Aprobar y publicar">
                           Aprobar
                        </a>
                        <a href="aprobar_publicacion.php?id=<?php echo $fila['id']; ?>&estado=rechazada" 
                           onclick="return confirm('¿Rechazar esta publicación? Se ocultará permanentemente.');"
                           class="boton-rechazar" title="Rechazar y ocultar">
                           Rechazar
                        </a>
                    <?php endif; ?>

                    <?php if ($rol_actual === ROL_ADMIN): ?>
                        <a href="javascript:void(0);" 
                           onclick="confirmarEliminacion(<?php echo $fila['id']; ?>)" 
                           class="boton-eliminar">
                           Eliminar
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>Aún no hay publicaciones en el sistema.</p>
    <?php endif; ?>
    
    <?php if ($total_pages > 1): ?>
        <div class="pagination" style="text-align: center; margin-top: 30px; margin-bottom: 20px;">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <?php 
                    $link = "admin.php?page={$i}";
                    $style = ($i == $pagina_actual) ? 
                        'background-color: #004d99; color: white; border: 1px solid #004d99;' : 
                        'background-color: white; color: #004d99; border: 1px solid #ccc;';
                ?>
                <a href="<?php echo $link; ?>" style="padding: 6px 12px; margin: 0 3px; text-decoration: none; border-radius: 4px; font-weight: bold; <?php echo $style; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>


    <script>
        let xhr = null; 

        function confirmarEliminacion(id) {
            if (confirm(" ¿Estás SEGURO de que quieres eliminar esta publicación y todos sus archivos? Esta acción es permanente.")) {
                window.location.href = 'eliminar_publicacion.php?id=' + id;
            }
        }
        
        function cancelUpload() {
            if (xhr) {
                xhr.abort(); 
                document.getElementById('statusMessage').textContent = 'Subida cancelada por el usuario.';
                document.getElementById('progressBar').style.backgroundColor = '#dc3545'; 
                document.getElementById('uploadForm').querySelector('button[type="submit"]').disabled = false;
                document.getElementById('progressContainer').style.display = 'none';
            }
        }

        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            e.preventDefault(); 
            
            const form = e.target;
            const formData = new FormData(form);
            const progressBar = document.getElementById('progressBar');
            const progressContainer = document.getElementById('progressContainer');
            const statusMessage = document.getElementById('statusMessage');
            const submitButton = form.querySelector('button[type="submit"]');

            // Resetear barra y mensajes
            progressBar.style.width = '0%';
            progressBar.textContent = '0%';
            progressBar.style.backgroundColor = '#007bff';
            statusMessage.textContent = 'Iniciando subida...';
            
            // Mostrar la barra de progreso y desactivar el botón
            progressContainer.style.display = 'block';
            submitButton.disabled = true;

            // Crear la solicitud AJAX
            xhr = new XMLHttpRequest();

            // Evento: Subida en progreso
            xhr.upload.addEventListener('progress', function(event) {
                if (event.lengthComputable) {
                    const percent = Math.round((event.loaded / event.total) * 100);
                    progressBar.style.width = percent + '%';
                    progressBar.textContent = percent + '%';
                    statusMessage.textContent = `Subiendo archivos: ${percent}% completado...`;
                }
            }, false);

            // Evento: Subida terminada (Respuesta del servidor)
            xhr.addEventListener('load', function() {
                if (xhr.status === 200) {
                    progressBar.style.width = '100%';
                    progressBar.textContent = '100%';
                    statusMessage.textContent = 'Subida completada. Procesando datos en el servidor...';
                    
                    // Reemplazar el contenido de la página con la respuesta de PHP (éxito o error)
                    document.documentElement.innerHTML = xhr.responseText;
                } else {
                    // Manejar errores HTTP (e.g., error 500 del servidor)
                    statusMessage.textContent = `Error del servidor (Código ${xhr.status}). Por favor, intente de nuevo.`;
                    progressBar.style.backgroundColor = '#dc3545'; 
                    submitButton.disabled = false;
                }
                xhr = null; 
            });

            // Evento: Errores de red
            xhr.addEventListener('error', function() {
                statusMessage.textContent = 'Error de red o conexión.';
                progressBar.style.backgroundColor = '#dc3545'; 
                submitButton.disabled = false;
                xhr = null;
            });
            
            xhr.open('POST', 'subir_collage.php');
            xhr.send(formData);
        });
    </script>

</body>

</html>
