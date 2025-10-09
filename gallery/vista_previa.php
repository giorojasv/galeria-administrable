<?php
include 'config.php';
verificar_sesion(); // Solo usuarios logueados pueden previsualizar

// Configuración de conexión
$host = 'localhost'; $user = ''; $password = ''; $db = '';
$conn = new mysqli($host, $user, $password, $db);

$publicacion_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($publicacion_id === 0) {
    die("ID de publicación no proporcionado.");
}

// Consulta que une usuarios y archivos para obtener la publicación y sus medios
$sql = "
    SELECT p.id, p.descripcion, p.fecha_publicacion, p.estado, u.usuario AS autor, a.nombre_archivo, a.tipo_contenido 
    FROM galeria_publicaciones p
    LEFT JOIN galeria_archivos a ON p.id = a.publicacion_id
    LEFT JOIN galeria_usuarios u ON p.usuario_id = u.id
    WHERE p.id = {$publicacion_id}
    ORDER BY a.id ASC
";
$resultado = $conn->query($sql);

$data = [];
$first_row = true;
if ($resultado->num_rows > 0) {
    while($fila = $resultado->fetch_assoc()) {
        if ($first_row) {
            $data['descripcion'] = $fila['descripcion'];
            $data['fecha'] = $fila['fecha_publicacion'];
            $data['estado'] = $fila['estado'];
            $data['autor'] = $fila['autor'];
            $first_row = false;
        }
        if ($fila['nombre_archivo']) {
            $data['archivos'][] = [
                'nombre' => $fila['nombre_archivo'],
                'tipo' => $fila['tipo_contenido']
            ];
        }
    }
}
$conn->close();

if (empty($data)) {
    die("Publicación no encontrada.");
}

$collage = $data; 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vista Previa: <?php echo htmlspecialchars(substr($collage['descripcion'], 0, 30)); ?>...</title>
    <style>
        /* Estilos Base (copiados de index_publica.php) */
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #fff3cd; }
        .aviso { 
            background-color: #dc3545; 
            color: white; 
            padding: 15px; 
            text-align: center; 
            font-weight: bold; 
            margin-bottom: 20px; 
            border-radius: 5px;
        }
        .publicacion { 
            background-color: white; 
            padding: 20px; 
            border: 3px solid #ffc107; 
            border-radius: 8px; 
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.2); 
        }
        .descripcion-pub { font-size: 1.5em; font-weight: bold; color: #333; margin-bottom: 10px; }
        .meta { font-size: 0.9em; color: #777; margin-bottom: 15px; }
        .collage-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 10px; }
        .collage-grid img, .collage-grid video { width: 100%; height: 150px; object-fit: cover; display: block; border-radius: 4px; }
        
        /* Estilos de Miniatura de Video (Icono de Play) */
        .archivo-miniatura { position: relative; cursor: pointer; }
        .archivo-miniatura.is-video::after {
            content: "\25B6"; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
            font-size: 3em; color: white; z-index: 5; text-shadow: 0 0 10px rgba(0,0,0,0.8);
            background-color: rgba(0, 0, 0, 0.5); border-radius: 50%; padding: 5px 15px 5px 18px;
            pointer-events: none;
        }
        .archivo-miniatura.is-video video { filter: brightness(0.7); }
        
        /* Estilos del Modal (Lightbox) - ¡Añadidos para la vista previa! */
        .modal {
            display: none; position: fixed; z-index: 1000; left: 0; top: 0;
            width: 100%; height: 100%; overflow: hidden; background-color: rgba(0,0,0,0.95);
        }
        .modal-contenido {
            display: flex; justify-content: center; align-items: center; width: 100%; height: 100%;
        }
        .modal-contenido img, .modal-contenido video {
            display: block; width: auto; height: auto; max-width: 95vw; max-height: 80vh; margin: 0 auto; border-radius: 5px;
        }
        #modalDescripcion {
            position: absolute; bottom: 0; left: 0; right: 0; color: white; padding: 15px; font-size: 1.1em;
            text-align: center; background: rgba(0, 0, 0, 0.5);
        }
        .cerrar {
            position: absolute; top: 10px; right: 25px; color: white; font-size: 40px; text-shadow: 0 0 5px black; cursor: pointer; z-index: 1001;
        }
        .nav-btn {
            cursor: pointer; position: absolute; top: 50%; width: auto; padding: 16px; margin-top: -22px;
            color: white; font-weight: bold; font-size: 25px; transition: 0.6s ease; user-select: none; z-index: 100;
        }
        .prev { left: 0; }
        .next { right: 0; }
        .nav-btn:hover { background-color: rgba(0, 0, 0, 0.8); }
    </style>
</head>
<body>
    <div class="aviso">
        ESTO ES UNA VISTA PREVIA - No es visible en la galería pública. 
        <br> (Estado actual: **<?php echo strtoupper($collage['estado']); ?>**)
    </div>

    <div class="publicacion" id="pub-<?php echo $collage['id']; ?>">
        <div class="descripcion-pub"><?php echo nl2br(htmlspecialchars($collage['descripcion'])); ?></div>
        <div class="meta">Publicado por: <?php echo htmlspecialchars($collage['autor']); ?> | Fecha: <?php echo date("d/m/Y H:i", strtotime($collage['fecha'])); ?></div>
        
        <div class="collage-grid">
            <?php if (!empty($collage['archivos'])): ?>
                <?php foreach ($collage['archivos'] as $clave => $archivo): ?>
                    <?php 
                    $ruta_archivo = "archivos_galeria/" . $archivo['nombre']; 
                    $descripcion_data = htmlspecialchars($collage['descripcion'], ENT_QUOTES, 'UTF-8');
                    ?>
                    
                    <div class="archivo-miniatura <?php echo ($archivo['tipo'] === 'video' ? 'is-video' : ''); ?>" 
                         data-ruta="<?php echo $ruta_archivo; ?>" 
                         data-tipo="<?php echo $archivo['tipo']; ?>"
                         data-descripcion="<?php echo $descripcion_data; ?>"
                         data-index="<?php echo $clave; ?>"
                         data-parent-id="<?php echo $collage['id']; ?>">
                        
                        <?php if ($archivo['tipo'] === 'imagen'): ?>
                            <img src="<?php echo $ruta_archivo; ?>" alt="Imagen de collage">
                        <?php else: ?>
                            <video src="<?php echo $ruta_archivo; ?>" title="Video"></video>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <p style="text-align: center; margin-top: 20px;">
        <a href="admin.php">Volver al Panel de Administración</a>
    </p>

    <div id="miModal" class="modal">
        <span class="cerrar" onclick="cerrarModal()">&times;</span>
        
        <a class="nav-btn prev" id="prevBtn" onclick="cambiarMedia(-1)">&#10094;</a>
        
        <div class="modal-contenido">
            </div>
        
        <a class="nav-btn next" id="nextBtn" onclick="cambiarMedia(1)">&#10095;</a>
        
        <div id="modalDescripcion"></div>
    </div>

    <script>
        let currentMediaList = []; 
        let currentIndex = 0;      

        const modal = document.getElementById('miModal');
        const modalContenido = modal.querySelector('.modal-contenido');
        const modalDescripcion = document.getElementById('modalDescripcion');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        
        document.addEventListener('DOMContentLoaded', function() {
            const miniaturas = document.querySelectorAll('.archivo-miniatura');

            miniaturas.forEach(miniatura => {
                miniatura.addEventListener('click', function() {
                    const parentId = this.getAttribute('data-parent-id');
                    const parentContainer = document.getElementById(`pub-${parentId}`);
                    
                    // 1. Recolectar todos los archivos de esta publicación
                    currentMediaList = [];
                    parentContainer.querySelectorAll('.archivo-miniatura').forEach(item => {
                        currentMediaList.push({
                            ruta: item.getAttribute('data-ruta'),
                            tipo: item.getAttribute('data-tipo'),
                            descripcion: item.getAttribute('data-descripcion')
                        });
                    });

                    // 2. Establecer el índice inicial
                    currentIndex = parseInt(this.getAttribute('data-index'));
                    
                    // 3. Mostrar el primer elemento y abrir el modal
                    mostrarMedia(currentIndex);
                    modal.style.display = "flex"; 
                });
            });

            // Teclas de navegación
            document.addEventListener('keydown', function(e) {
                if (modal.style.display === 'flex') {
                    if (e.key === 'Escape') {
                        cerrarModal();
                    } else if (e.key === 'ArrowRight') {
                        cambiarMedia(1);
                    } else if (e.key === 'ArrowLeft') {
                        cambiarMedia(-1);
                    }
                }
            });
        });
        
        function mostrarMedia(index) {
            if (index < 0 || index >= currentMediaList.length) return; 

            currentIndex = index;
            const media = currentMediaList[currentIndex];
            
            modalContenido.innerHTML = ''; 
            modalDescripcion.innerHTML = media.descripcion.replace(/\n/g, '<br>');
            
            let elemento;
            if (media.tipo === 'imagen') {
                elemento = document.createElement('img');
                elemento.src = media.ruta;
                elemento.alt = media.descripcion;
            } else if (media.tipo === 'video') {
                elemento = document.createElement('video');
                elemento.src = media.ruta;
                elemento.controls = true;
                elemento.autoplay = true; 
            }

            if (elemento) {
                modalContenido.appendChild(elemento);
            }
            
            // Ocultar botones de navegación si es el primero o el último
            prevBtn.style.display = (currentIndex === 0) ? 'none' : 'block';
            nextBtn.style.display = (currentIndex === currentMediaList.length - 1) ? 'none' : 'block';
        }
        
        function cambiarMedia(direccion) {
            mostrarMedia(currentIndex + direccion);
        }
        
        function cerrarModal() {
            modal.style.display = "none";
            const video = modalContenido.querySelector('video');
            if (video) { video.pause(); } // Detener el video al cerrar
        }
        
        window.onclick = function(event) {
            if (event.target == modal) {
                cerrarModal();
            }
        }
    </script>
</body>

</html>
