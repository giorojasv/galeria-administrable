<?php
// Configuración de conexión
$host = 'localhost'; $user = 'academia_galeria'; $password = '@galeria2025'; $db = 'academia_galeria';
$conn = new mysqli($host, $user, $password, $db);
if ($conn->connect_error) { die("Conexión fallida: " . $conn->connect_error); }


// --- CONFIGURACIÓN DE PAGINACIÓN ---
$publicaciones_por_pagina = 10;
$pagina_actual = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($pagina_actual - 1) * $publicaciones_por_pagina;

$where_clause_publica = " WHERE p.estado = 'aprobada'"; 

// 1. Consulta para OBTENER EL NÚMERO TOTAL de publicaciones aprobadas
$sql_count = "SELECT COUNT(DISTINCT p.id) AS total FROM galeria_publicaciones p {$where_clause_publica}";
$resultado_count = $conn->query($sql_count);
$total_publicaciones = $resultado_count->fetch_assoc()['total'];
$total_pages = ceil($total_publicaciones / $publicaciones_por_pagina);

// 2. Consulta para OBTENER las publicaciones de la página actual (con LIMIT y OFFSET)
$sql = "
    SELECT p.id, p.descripcion, p.fecha_publicacion, a.nombre_archivo, a.tipo_contenido 
    FROM galeria_publicaciones p
    JOIN galeria_archivos a ON p.id = a.publicacion_id
    {$where_clause_publica}
    ORDER BY p.fecha_publicacion DESC
    LIMIT {$publicaciones_por_pagina} OFFSET {$offset}
";
$resultado = $conn->query($sql);

$collages = [];
if ($resultado->num_rows > 0) {
    while($fila = $resultado->fetch_assoc()) {
        $collages[$fila['id']]['descripcion'] = $fila['descripcion'];
        $collages[$fila['id']]['fecha'] = $fila['fecha_publicacion'];
        $collages[$fila['id']]['archivos'][] = [
            'nombre' => $fila['nombre_archivo'],
            'tipo' => $fila['tipo_contenido']
        ];
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>Galería de Eventos | Establecimiento Educacional</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; margin: 0; padding: 0; background-color: #f7f9fc; color: #333; }
        .contenedor-principal { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        h1 { text-align: center; color: #004d99; margin-bottom: 30px; font-size: 2.5em; border-bottom: 3px solid #004d99; display: inline-block; padding-bottom: 10px; width: 100%; }
        .publicacion { background-color: white; margin-bottom: 30px; padding: 20px; border-radius: 12px; box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1); transition: transform 0.3s ease; }
        .descripcion-pub { font-size: 1.2em; font-weight: 600; color: #333; margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px dashed #eee; }
        .fecha-pub { font-size: 0.9em; color: #777; margin-bottom: 15px; display: block; }
        .collage-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 10px; }
        .collage-grid .archivo-miniatura { cursor: pointer; overflow: hidden; border-radius: 8px; position: relative; }
        .collage-grid img, .collage-grid video { width: 100%; height: 150px; object-fit: cover; display: block; transition: transform 0.3s ease; }

        /* Icono de Play */
        .collage-grid .archivo-miniatura.is-video::after { content: "\25B6"; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 4em; color: white; z-index: 5; text-shadow: 0 0 10px rgba(0,0,0,0.8); background-color: rgba(0, 0, 0, 0.5); border-radius: 50%; padding: 10px 20px 10px 25px; pointer-events: none; }
        .collage-grid .archivo-miniatura.is-video video { filter: brightness(0.7); }

        /* Estilos del Modal (Lightbox) */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: hidden; background-color: rgba(0,0,0,0.95); }
        .modal-contenido { display: flex; justify-content: center; align-items: center; width: 100%; height: 100%; }
        .modal-contenido img, .modal-contenido video { display: block; width: auto; height: auto; max-width: 95vw; max-height: 80vh; margin: 0 auto; border-radius: 5px; }
        #modalDescripcion { position: absolute; bottom: 0; left: 0; right: 0; color: white; padding: 15px; font-size: 1.1em; text-align: center; background: rgba(0, 0, 0, 0.5); }
        .cerrar { position: absolute; top: 10px; right: 25px; color: white; font-size: 40px; text-shadow: 0 0 5px black; cursor: pointer; z-index: 1001; }
        .nav-btn { cursor: pointer; position: absolute; top: 50%; width: auto; padding: 16px; margin-top: -22px; color: white; font-weight: bold; font-size: 25px; transition: 0.6s ease; user-select: none; z-index: 100; }
        .prev { left: 0; }
        .next { right: 0; }
        .nav-btn:hover { background-color: rgba(0, 0, 0, 0.8); }
        
        .pagination a { padding: 8px 15px; margin: 0 4px; text-decoration: none; border-radius: 4px; font-weight: bold; }
    </style>
</head>
<body>

    <div class="contenedor-principal">
        <h1>Galería de Collages y Eventos</h1>
        
        <?php if (!empty($collages)): ?>
            <?php foreach ($collages as $id => $data): ?>
                <div class="publicacion" id="pub-<?php echo $id; ?>">
                    <div class="descripcion-pub">
                        <?php echo nl2br(htmlspecialchars($data['descripcion'])); ?>
                    </div>
                    
                    <span class="fecha-pub">Publicado el: <?php echo date("d/m/Y H:i", strtotime($data['fecha'])); ?></span>
                    
                    <div class="collage-grid">
                        <?php foreach ($data['archivos'] as $clave => $archivo): ?>
                            <?php 
                            $ruta_archivo = "archivos_galeria/" . $archivo['nombre']; 
                            $descripcion_data = htmlspecialchars($data['descripcion'], ENT_QUOTES, 'UTF-8');
                            ?>
                            
                            <div class="archivo-miniatura <?php echo ($archivo['tipo'] === 'video' ? 'is-video' : ''); ?>" 
                                 data-ruta="<?php echo $ruta_archivo; ?>" 
                                 data-tipo="<?php echo $archivo['tipo']; ?>"
                                 data-descripcion="<?php echo $descripcion_data; ?>"
                                 data-index="<?php echo $clave; ?>"
                                 data-parent-id="<?php echo $id; ?>">
                                
                                <?php if ($archivo['tipo'] === 'imagen'): ?>
                                    <img src="<?php echo $ruta_archivo; ?>" alt="Imagen de collage">
                                <?php else: ?>
                                    <video src="<?php echo $ruta_archivo; ?>" title="Clic para ver video completo"></video>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align: center; color: #777;">Aún no hay publicaciones aprobadas en la galería.</p>
        <?php endif; ?>
        
        <?php if ($total_pages > 1): ?>
            <div class="pagination" style="text-align: center; margin-top: 40px; margin-bottom: 20px;">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php 
                        $link = "index_publica.php?page={$i}";
                        $style = ($i == $pagina_actual) ? 
                            'background-color: #004d99; color: white; border: 1px solid #004d99;' : 
                            'background-color: white; color: #004d99; border: 1px solid #ccc;';
                    ?>
                    <a href="<?php echo $link; ?>" style="padding: 8px 15px; margin: 0 4px; text-decoration: none; border-radius: 4px; font-weight: bold; <?php echo $style; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>

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
            
            prevBtn.style.display = (currentIndex === 0) ? 'none' : 'block';
            nextBtn.style.display = (currentIndex === currentMediaList.length - 1) ? 'none' : 'block';
        }
        
        function cambiarMedia(direccion) {
            mostrarMedia(currentIndex + direccion);
        }
        
        function cerrarModal() {
            modal.style.display = "none";
            const video = modalContenido.querySelector('video');
            if (video) { video.pause(); } 
        }
        
        window.onclick = function(event) {
            if (event.target == modal) {
                cerrarModal();
            }
        }
    </script>

</body>
</html>