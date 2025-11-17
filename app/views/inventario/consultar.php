<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PRESTLAB - Inventario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        /* Estilos generales del cuerpo */
        body {
            background-color: #f8f9fa; /* Fondo gris claro */
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        /* Barra de navegación con gradiente personalizado */
        .navbar-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        /* Estilos de las tarjetas de equipo (cards) */
        .equipment-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s; /* Transición para efecto hover */
            background: white;
        }
        /* Efecto hover en las tarjetas de equipo */
        .equipment-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        /* Estilos base para las insignias (badges) de estado */
        .badge-status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
        }
        /* Clases específicas para cada estado de equipo */
        .badge-disponible { background-color: #28a745; color: white; }
        .badge-prestado { background-color: #ffc107; color: #000; }
        .badge-mantenimiento { background-color: #dc3545; color: white; }
        .badge-baja { background-color: #6c757d; color: white; }
        /* Contenedor de búsqueda con sombra */
        .search-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        /* Estilos de paginación para coincidir con el tema */
        .pagination .page-item.active .page-link { background-color: #667eea; border-color: #667eea; }
        .pagination .page-link { color: #667eea; }
        .pagination .page-link:hover { color: #764ba2; }
        .pagination .page-item.disabled .page-link { color: #6c757d; }
        /* Estilo para las imágenes de los equipos */
        .equipment-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
        <div class="container">
            <a class="navbar-brand" href="#"><i class="bi bi-box-seam"></i> PRESTLAB</a>
            <div class="d-flex align-items-center">
                <span class="navbar-text me-3">
                    <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars(Session::get('user_nombre')); ?>
                </span>
                <div class="btn-group">
                    <a href="<?php echo Session::get('user_rol') == 1 ? '/prestlab/public/admin/dashboard.php' : '/prestlab/public/usuario/dashboard.php'; ?>" 
                       class="btn btn-outline-light btn-sm">
                        <i class="bi bi-house"></i> Dashboard
                    </a>
                    <a href="/prestlab/public/index.php?action=logout" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-box-arrow-right"></i> Salir
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <?php 
    // Comprueba si existe un mensaje de préstamo en la sesión
    if (isset($_SESSION['mensaje_prestamo'])): 
    ?>
        <div class="container mt-4">
            <div class="alert alert-<?php echo htmlspecialchars($_SESSION['tipo_mensaje_prestamo']); ?> alert-dismissible fade show" role="alert">
                <i class="bi <?php echo $_SESSION['tipo_mensaje_prestamo'] == 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle'; ?>"></i>
                <?php echo htmlspecialchars($_SESSION['mensaje_prestamo']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
        <?php
        // Limpia las variables de sesión para que el mensaje no se muestre de nuevo al recargar
        unset($_SESSION['mensaje_prestamo']);
        unset($_SESSION['tipo_mensaje_prestamo']);
        ?>
    <?php endif; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2>Inventario de Equipos</h2>
                <p class="text-muted">Consulta y busca equipos disponibles para préstamo</p>
            </div>
            <div class="text-end">
                <small class="text-muted">
                    Mostrando <?php echo count($equipos); ?> de <?php echo $total_equipos; ?> equipos
                </small>
            </div>
        </div>
        
        <div class="search-card">
            <div class="card-body">
                <form method="GET" action="/prestlab/public/index.php">
                    <input type="hidden" name="controller" value="inventario">
                    <input type="hidden" name="action" value="consultar">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" class="form-control" name="nombre" 
                                        placeholder="Buscar por nombre de equipo..."
                                        value="<?php echo htmlspecialchars($filtros_actuales['nombre'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <select class="form-select" name="estado">
                                <option value="">Todos los estados</option>
                                <?php foreach ($estados_equipo as $estado): ?>
                                    <option value="<?php echo htmlspecialchars($estado['id_estado_equipo']); ?>"
                                        <?php 
                                        // Marca como seleccionado si coincide con el filtro actual
                                        echo (isset($filtros_actuales['estado']) && $filtros_actuales['estado'] == $estado['id_estado_equipo']) ? 'selected' : ''; 
                                        ?>>
                                        <?php echo htmlspecialchars($estado['nombre_estado']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-funnel"></i> Filtrar
                            </button>
                        </div>
                    </div>
                </form>
                
                <?php if (!empty($filtros_actuales)): ?>
                    <div class="mt-3">
                        <small class="text-muted">Filtros activos:</small>
                        <?php 
                        // Itera sobre los filtros actuales (nombre, estado)
                        foreach ($filtros_actuales as $key => $value): 
                        ?>
                            <?php if (!empty($value) && $key != 'pagina'): // Ignora filtros vacíos y la paginación ?>
                                <span class="badge bg-secondary me-1">
                                    <?php 
                                    // Muestra el nombre del estado si es un filtro por estado
                                    if ($key == 'estado') {
                                        foreach ($estados_equipo as $estado) {
                                            if ($estado['id_estado_equipo'] == $value) {
                                                echo htmlspecialchars($estado['nombre_estado']);
                                                break;
                                            }
                                        }
                                    } else {
                                        // Muestra el valor del filtro (ej: la palabra de búsqueda)
                                        echo htmlspecialchars($value);
                                    }
                                    ?>
                                    <a href="?<?php 
                                        // Reconstruye la URL sin el filtro actual
                                        $filtros_sin_este = $filtros_actuales;
                                        unset($filtros_sin_este[$key]);
                                        echo http_build_query(array_merge(
                                            ['controller' => 'inventario', 'action' => 'consultar'], 
                                            $filtros_sin_este
                                        )); 
                                    ?>" class="text-white ms-1" style="text-decoration: none;">×</a>
                                </span>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <a href="/prestlab/public/index.php?controller=inventario&action=consultar" class="btn btn-sm btn-outline-secondary ms-2">
                            <i class="bi bi-x-circle"></i> Limpiar filtros
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (empty($equipos)): ?>
            <div class="alert alert-info text-center">
                <i class="bi bi-info-circle"></i> 
                <?php echo !empty($filtros_actuales) ? 
                    "No se encontraron equipos que coincidan con la búsqueda." : 
                    "No hay equipos registrados en el inventario."; ?>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($equipos as $equipo): ?>
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="equipment-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="me-3" style="width: 80px; height: 80px; flex-shrink: 0;">
                                    <?php
                                    $imagen_url = !empty($equipo['imagen_url']) ? $equipo['imagen_url'] : 
                                        '/prestlab/public/assets/images/equipos/default.png';
                                    ?>
                                    <img src="<?php echo htmlspecialchars($imagen_url); ?>" 
                                         alt="<?php echo htmlspecialchars($equipo['nombre_equipo']); ?>"
                                         class="equipment-image"
                                         onerror="this.src='/prestlab/public/assets/images/equipos/default.png'">
                                </div>
                                
                                <div style="flex: 1;">
                                    <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($equipo['nombre_equipo']); ?></h6>
                                    <p class="text-muted mb-2 small">
                                        <?php 
                                        // Muestra una descripción truncada
                                        echo !empty($equipo['descripcion']) ? 
                                        htmlspecialchars(mb_strimwidth($equipo['descripcion'], 0, 60, "...")) : 
                                        'Sin descripción'; 
                                        ?>
                                    </p>
                                    
                                    <?php
                                    $badge_class = '';
                                    $badge_icon = '';
                                    switch($equipo['id_estado_equipo']) {
                                        case 1: $badge_class = 'badge-disponible'; $badge_icon = 'bi-check-circle'; break;
                                        case 2: $badge_class = 'badge-prestado'; $badge_icon = 'bi-clock'; break;
                                        case 3: $badge_class = 'badge-mantenimiento'; $badge_icon = 'bi-tools'; break;
                                        case 4: $badge_class = 'badge-baja'; $badge_icon = 'bi-x-circle'; break;
                                        default: $badge_class = 'badge-secondary'; $badge_icon = 'bi-question-circle';
                                    }
                                    ?>
                                    <span class="badge badge-status <?php echo $badge_class; ?>">
                                        <i class="bi <?php echo $badge_icon; ?>"></i>
                                        <?php echo htmlspecialchars($equipo['nombre_estado']); ?>
                                    </span>
                                </div>
                                <div class="text-end">
                                    <div class="badge bg-<?php echo $equipo['cantidad_disponible'] > 0 ? 'success' : 'danger'; ?>">
                                        <?php echo htmlspecialchars($equipo['cantidad_disponible']); ?> disp.
                                    </div>
                                </div>
                            </div>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="bi bi-calendar"></i> 
                                    <?php echo date('d/m/Y', strtotime($equipo['fecha_adquisicion'])); ?>
                                </small>
                                <div>
                                    <?php 
                                    // El botón se habilita solo si está disponible (ID 1) y hay stock
                                    if ($equipo['id_estado_equipo'] == 1 && $equipo['cantidad_disponible'] > 0): 
                                    ?>
                                        <button class="btn btn-sm btn-primary solicitar-prestamo" 
                                                data-equipo-id="<?php echo htmlspecialchars($equipo['id_equipo']); ?>"
                                                data-equipo-nombre="<?php echo htmlspecialchars($equipo['nombre_equipo']); ?>">
                                            <i class="bi bi-plus-circle"></i> Solicitar
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-secondary" disabled>
                                            <i class="bi <?php echo $badge_icon; ?>"></i>
                                            <?php echo htmlspecialchars($equipo['nombre_estado']); ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php 
            // La paginación solo se muestra si hay más de una página
            if ($total_paginas > 1): 
            ?>
                <div class="row mt-4">
                    <div class="col-md-6">
                        <p class="text-muted small">
                            Mostrando <?php echo (($pagina_actual - 1) * $elementos_por_pagina) + 1; ?> 
                            a <?php echo min($pagina_actual * $elementos_por_pagina, $total_equipos); ?> 
                            de <?php echo $total_equipos; ?> equipos
                        </p>
                    </div>
                    <div class="col-md-6">
                        <nav aria-label="Paginación de equipos">
                            <ul class="pagination justify-content-end mb-0">
                                <?php 
                                // Combina los filtros actuales con la paginación y el controlador/acción
                                $base_params = ['controller' => 'inventario', 'action' => 'consultar'] + $filtros_actuales; 
                                ?>
                                
                                <li class="page-item <?php echo $pagina_actual <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" 
                                        href="?<?php 
                                            echo http_build_query($base_params + ['pagina' => $pagina_actual - 1]);
                                        ?>"
                                        aria-label="Anterior">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                                
                                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                    <li class="page-item <?php echo $i == $pagina_actual ? 'active' : ''; ?>">
                                        <a class="page-link" 
                                            href="?<?php echo http_build_query($base_params + ['pagina' => $i]); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <li class="page-item <?php echo $pagina_actual >= $total_paginas ? 'disabled' : ''; ?>">
                                    <a class="page-link" 
                                        href="?<?php 
                                            echo http_build_query($base_params + ['pagina' => $pagina_actual + 1]);
                                        ?>"
                                        aria-label="Siguiente">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            <?php endif; ?>
            <?php endif; ?>
    </div>

    <div class="modal fade" id="modalSolicitarPrestamo" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    <h5 class="modal-title">Solicitar Préstamo</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="info-equipo"></div>
                    <form id="form-solicitar-prestamo">
                        <input type="hidden" id="equipo_id" name="equipo_id">
                        
                        <div class="mb-3">
                            <label for="cantidad" class="form-label">Cantidad a solicitar</label>
                            <input type="number" class="form-control" id="cantidad" name="cantidad" value="1" min="1">
                            <div class="form-text" id="disponibilidad-texto"></div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="fecha_limite" class="form-label">Fecha límite de devolución</label>
                            <input type="date" class="form-control" id="fecha_limite" name="fecha_limite" 
                                    min="<?php echo date('Y-m-d', strtotime('+1 day')); // Mínimo mañana ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="observaciones" class="form-label">Observaciones (opcional)</label>
                            <textarea class="form-control" id="observaciones" name="observaciones" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btn-confirmar-solicitud">
                        <i class="bi bi-send"></i> Solicitar Préstamo
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // JavaScript para manejar la apertura del modal y la solicitud de préstamos
        document.addEventListener('DOMContentLoaded', function() {
            const modal = new bootstrap.Modal(document.getElementById('modalSolicitarPrestamo'));
            const disponibilidadTexto = document.getElementById('disponibilidad-texto');
            const cantidadInput = document.getElementById('cantidad');
            const infoEquipo = document.getElementById('info-equipo');
            
            // 1. Manejar clic en botones 'Solicitar'
            document.querySelectorAll('.solicitar-prestamo').forEach(button => {
                button.addEventListener('click', function() {
                    const equipoId = this.getAttribute('data-equipo-id');
                    const equipoNombre = this.getAttribute('data-equipo-nombre');
                    
                    // a) Actualizar modal con información inicial del equipo
                    document.getElementById('equipo_id').value = equipoId;
                    infoEquipo.innerHTML = `
                        <div class="alert alert-info">
                            <strong>Equipo:</strong> ${equipoNombre}<br>
                            <span id="estado-disponibilidad">Verificando disponibilidad...</span>
                        </div>
                    `;
                    
                    // b) Llamada AJAX para verificar disponibilidad en tiempo real
                    verificarDisponibilidad(equipoId);
                    
                    // c) Mostrar modal
                    modal.show();
                });
            });
            
            // 2. Función para verificar disponibilidad mediante Fetch API
            function verificarDisponibilidad(equipoId) {
                // Endpoint simulado para obtener disponibilidad
                fetch(`/prestlab/public/index.php?controller=inventario&action=verificarDisponibilidad&id_equipo=${equipoId}`)
                    .then(response => response.json())
                    .then(data => {
                        const estadoElement = document.getElementById('estado-disponibilidad');
                        
                        if (data.disponible) {
                            // Si está disponible
                            estadoElement.innerHTML = `<span class="text-success">✓ Disponible - ${data.cantidad_disponible} unidades</span>`;
                            disponibilidadTexto.textContent = `Puedes solicitar hasta ${data.cantidad_disponible} unidades`;
                            cantidadInput.max = data.cantidad_disponible;
                            cantidadInput.value = 1;
                            cantidadInput.disabled = false;
                        } else {
                            // Si no está disponible
                            estadoElement.innerHTML = `<span class="text-danger">✗ No disponible - Estado: ${data.estado}</span>`;
                            disponibilidadTexto.textContent = 'Este equipo no está disponible para préstamo';
                            cantidadInput.disabled = true;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        disponibilidadTexto.textContent = 'Error al verificar disponibilidad';
                    });
            }
            
            // 3. Manejar cambio en la cantidad (para no exceder el máximo disponible)
            cantidadInput.addEventListener('change', function() {
                const max = parseInt(this.max);
                const valor = parseInt(this.value);
                
                if (valor > max) {
                    this.value = max;
                    disponibilidadTexto.textContent = `Máximo permitido: ${max} unidades`;
                    disponibilidadTexto.className = 'form-text text-danger';
                } else if (valor <= 0) {
                    this.value = 1;
                } else {
                    disponibilidadTexto.className = 'form-text text-muted';
                }
            });
            
            // 4. Confirmar solicitud (envío del formulario vía POST)
            document.getElementById('btn-confirmar-solicitud').addEventListener('click', function() {
                const equipoId = document.getElementById('equipo_id').value;
                const cantidad = document.getElementById('cantidad').value;
                const fechaLimite = document.getElementById('fecha_limite').value;
                const observaciones = document.getElementById('observaciones').value;

                // Validaciones básicas antes de enviar
                if (!fechaLimite) {
                    alert('Por favor, selecciona una fecha límite de devolución.');
                    return;
                }
                if (cantidad <= 0 || parseInt(cantidad) > parseInt(cantidadInput.max)) {
                    alert('La cantidad solicitada no es válida o supera la disponibilidad.');
                    return;
                }

                // Crea y envía el formulario dinámicamente con los datos del modal
                const form = document.createElement('form');
                form.method = 'POST';
                // La solicitud real se dirige al controlador de Préstamo
                form.action = '/prestlab/public/index.php?controller=prestamo&action=solicitar';

                // Crea campos ocultos para cada dato y los añade al formulario
                const inputs = [
                    { name: 'equipo_id', value: equipoId },
                    { name: 'cantidad', value: cantidad },
                    { name: 'fecha_limite', value: fechaLimite },
                    { name: 'observaciones', value: observaciones }
                ];

                inputs.forEach(data => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = data.name;
                    input.value = data.value;
                    form.appendChild(input);
                });

                document.body.appendChild(form);
                form.submit(); // Envía la solicitud POST al controlador
            });
        });
    </script>
</body>
</html>