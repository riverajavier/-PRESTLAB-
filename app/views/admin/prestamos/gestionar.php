<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PRESTLAB - Gestión de Préstamos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .navbar-custom { background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%); }
        .sidebar { background-color: #2c3e50; min-height: calc(100vh - 76px); padding: 0; }
        .sidebar .nav-link { color: rgba(255,255,255,0.8); padding: 15px 20px; border-left: 3px solid transparent; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background-color: rgba(255,255,255,0.1); border-left-color: #3498db; color: white; }
        .stat-card { background: white; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; }
        .prestamo-card { background: white; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border-left: 4px solid #667eea; }
        .badge-estado { padding: 6px 12px; border-radius: 20px; font-size: 0.85rem; }
        .badge-activo { background-color: #28a745; color: white; }
        .badge-vencido { background-color: #dc3545; color: white; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand" href="#"><i class="bi bi-person-badge"></i> PRESTLAB - Admin</a>
            <div class="d-flex align-items-center">
                <span class="navbar-text me-3"><i class="bi bi-person-circle"></i> <?php echo Session::get('user_nombre'); ?></span>
                <a href="/prestlab/public/index.php?action=logout" class="btn btn-outline-light btn-sm"><i class="bi bi-box-arrow-right"></i> Salir</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <nav class="nav flex-column">
                    <a class="nav-link" href="/prestlab/public/admin/dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                    <a class="nav-link" href="/prestlab/public/index.php?controller=inventario&action=gestionarInventario"><i class="bi bi-box-seam"></i> Inventario</a>
                    <a class="nav-link active" href="/prestlab/public/index.php?controller=prestamo&action=gestionarPrestamos"><i class="bi bi-clipboard-check"></i> Préstamos</a>
                    <a class="nav-link" href="/prestlab/public/index.php?controller=devolucion&action=gestionarDevoluciones"><i class="bi bi-arrow-return-left"></i> Devoluciones</a>
                    <a class="nav-link" href="/prestlab/public/index.php?controller=usuario&action=gestionarUsuarios"><i class="bi bi-people"></i> Usuarios</a>
                </nav>
            </div>

            <!-- Contenido principal -->
            <div class="col-md-9 col-lg-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2>Gestión de Préstamos</h2>
                        <p class="text-muted">Administra los préstamos activos del sistema</p>
                    </div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalPrestamoPresencial">
                        <i class="bi bi-plus-circle"></i> Préstamo Presencial
                    </button>
                </div>

                <!-- Estadísticas -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h3><?php echo $estadisticas['total_prestamos']; ?></h3>
                            <p class="text-muted mb-0">Total Préstamos</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h3><?php echo $estadisticas['prestamos_activos']; ?></h3>
                            <p class="text-muted mb-0">Préstamos Activos</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h3><?php echo $estadisticas['prestamos_vencidos']; ?></h3>
                            <p class="text-muted mb-0">Préstamos Vencidos</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h3><?php echo $estadisticas['usuarios_con_prestamos']; ?></h3>
                            <p class="text-muted mb-0">Usuarios Activos</p>
                        </div>
                    </div>
                </div>

                <!-- Lista de préstamos activos -->
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-list-check"></i> Préstamos Activos</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($prestamos_activos)): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-clipboard-check" style="font-size: 3rem; color: #6c757d;"></i>
                                <h5 class="mt-3">No hay préstamos activos</h5>
                                <p class="text-muted">Todos los préstamos han sido completados o devueltos</p>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($prestamos_activos as $prestamo): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="prestamo-card">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <div>
                                                    <h5 class="mb-1"><?php echo htmlspecialchars($prestamo['codigo_prestamo']); ?></h5>
                                                    <p class="text-muted mb-0">
                                                        <i class="bi bi-person"></i> 
                                                        <?php echo htmlspecialchars($prestamo['nombre'] . ' ' . $prestamo['apellido']); ?>
                                                    </p>
                                                    <small class="text-muted"><?php echo htmlspecialchars($prestamo['correo']); ?></small>
                                                </div>
                                                <div>
                                                    <?php
                                                    $badge_class = $prestamo['id_estado_prestamo'] == 1 ? 'badge-activo' : 'badge-vencido';
                                                    ?>
                                                    <span class="badge-estado <?php echo $badge_class; ?>">
                                                        <?php echo htmlspecialchars($prestamo['estado_prestamo']); ?>
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- ✅ PASO 1: BLOQUE ACTUALIZADO - Detalle dinámico de equipos -->
                                            <div class="mb-3">
                                                <small class="text-muted">Equipos solicitados:</small>
                                                <ul class="list-unstyled mb-2">
                                                    <?php
                                                    $detalles = $this->prestamoModel->obtenerDetallePrestamo($prestamo['id_prestamo']);
                                                    foreach ($detalles as $d):
                                                    ?>
                                                        <li>
                                                            <strong><?= htmlspecialchars($d['nombre_equipo']) ?></strong>
                                                            <br><small class="text-muted">Código: <?= htmlspecialchars($d['id_equipo']) ?> | Cantidad: <?= $d['cantidad'] ?></small>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>

                                                <!-- Botón Editar (solo admin) -->
                                                <button class="btn btn-sm btn-outline-warning editar-prestamo"
                                                        data-prestamo-id="<?= $prestamo['id_prestamo'] ?>"
                                                        data-prestamo-codigo="<?= htmlspecialchars($prestamo['codigo_prestamo']) ?>"
                                                        data-fecha-limite="<?= $prestamo['fecha_limite_devolucion'] ?>">
                                                    <i class="bi bi-pencil"></i> Editar
                                                </button>
                                            </div>

                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted">
                                                    <i class="bi bi-clock"></i> 
                                                    Límite: <?php echo date('d/m/Y', strtotime($prestamo['fecha_limite_devolucion'])); ?>
                                                </small>
                                                <small class="text-muted">
                                                    <i class="bi bi-calendar"></i> 
                                                    Inicio: <?php echo date('d/m/Y', strtotime($prestamo['fecha_prestamo'])); ?>
                                                </small>
                                            </div>

                                            <?php if ($prestamo['id_estado_prestamo'] == 2): ?>
                                                <div class="mt-2">
                                                    <small class="text-danger">
                                                        <i class="bi bi-exclamation-triangle"></i> 
                                                        PRÉSTAMO VENCIDO
                                                    </small>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Préstamo Presencial -->
    <div class="modal fade" id="modalPrestamoPresencial" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Registrar Préstamo Presencial</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="form-prestamo-presencial">
                        <div class="mb-3">
                            <label class="form-label">Usuario</label>
                            <select class="form-select" id="select-usuario" name="id_usuario" required>
                                <option value="">Seleccionar usuario...</option>
                                <!-- Se llenará con JavaScript -->
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Fecha Límite de Devolución</label>
                            <input type="date" class="form-control" name="fecha_limite" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Agregar Equipos</label>
                            <div class="row mb-2">
                                <div class="col-md-6">
                                    <select class="form-select" id="select-equipo">
                                        <option value="">Seleccionar equipo...</option>
                                        <!-- Se llenará con JavaScript -->
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <input type="number" class="form-control" id="input-cantidad" min="1" value="1" placeholder="Cantidad">
                                </div>
                                <div class="col-md-3">
                                    <button type="button" class="btn btn-outline-primary w-100" id="btn-agregar-equipo">
                                        <i class="bi bi-plus"></i> Agregar
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Equipos Seleccionados</label>
                            <div id="lista-equipos" class="border rounded p-3" style="min-height: 100px; max-height: 200px; overflow-y: auto;">
                                <p class="text-muted text-center mb-0">No hay equipos seleccionados</p>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btn-confirmar-prestamo">Registrar Préstamo</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ✅ PASO 2: MODAL PARA EDITAR PRÉSTAMO -->
    <!-- Modal Editar Préstamo -->
    <div class="modal fade" id="modalEditarPrestamo" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header bg-warning text-dark">
            <h5 class="modal-title">Editar Préstamo</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <form id="form-editar-prestamo">
              <input type="hidden" id="edit_id_prestamo" name="id_prestamo">

              <div class="mb-3">
                <label class="form-label">Código de Préstamo</label>
                <input type="text" class="form-control" id="edit_codigo_prestamo" readonly>
              </div>

              <div class="mb-3">
                <label class="form-label">Fecha Límite de Devolución</label>
                <input type="date" class="form-control" id="edit_fecha_limite" name="fecha_limite" required>
              </div>

              <!-- 🔧 PASO 2: SECCIÓN ACTUALIZADA - Equipos Actuales -->
              <div class="mb-3">
                <label class="form-label">Equipos Actuales</label>
                <div id="equipos-actuales" class="border rounded p-3 bg-light">
                    <!-- Se llenará dinámicamente -->
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label">Agregar Equipos (opcional)</label>
                <select class="form-select" id="select-equipo-agregar">
                    <option value="">Seleccionar equipo...</option>
                    <?php foreach ($equipos_disponibles as $eq): ?>
                        <option value="<?= $eq['id_equipo'] ?>" data-max="<?= $eq['cantidad_disponible'] ?>">
                            <?= htmlspecialchars($eq['nombre_equipo']) ?> (<?= $eq['cantidad_disponible'] ?> disp.)
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="btn btn-sm btn-primary mt-2" id="btn-agregar-equipo-editar">Agregar</button>
              </div>

              <div id="lista-nuevos-equipos" class="mb-3"></div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="button" class="btn btn-warning" id="btn-guardar-cambios">Guardar Cambios</button>
          </div>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const equiposSeleccionados = [];
            
            // Cargar usuarios y equipos
            Promise.all([
                fetch('/prestlab/public/index.php?controller=usuario&action=obtenerUsuarios').then(r => r.json()),
                fetch('/prestlab/public/index.php?controller=inventario&action=obtenerEquipos').then(r => r.json())
            ]).then(([usuariosData, equiposData]) => {
                if (usuariosData.success) {
                    const selectUsuario = document.getElementById('select-usuario');
                    usuariosData.usuarios.forEach(usuario => {
                        const option = document.createElement('option');
                        option.value = usuario.id_usuario;
                        option.textContent = `${usuario.nombre} ${usuario.apellido} - ${usuario.correo}`;
                        selectUsuario.appendChild(option);
                    });
                }

                if (equiposData.success) {
                    const selectEquipo = document.getElementById('select-equipo');
                    equiposData.equipos.forEach(equipo => {
                        if (equipo.cantidad_disponible > 0 && equipo.id_estado_equipo === 1) {
                            const option = document.createElement('option');
                            option.value = equipo.id_equipo;
                            option.textContent = `${equipo.nombre_equipo} (${equipo.cantidad_disponible} disponibles)`;
                            option.setAttribute('data-max', equipo.cantidad_disponible);
                            selectEquipo.appendChild(option);
                        }
                    });
                }
            });

            // Agregar equipo a la lista
            document.getElementById('btn-agregar-equipo').addEventListener('click', function() {
                const selectEquipo = document.getElementById('select-equipo');
                const inputCantidad = document.getElementById('input-cantidad');
                const equipoId = selectEquipo.value;
                const cantidad = parseInt(inputCantidad.value);
                const maxCantidad = parseInt(selectEquipo.selectedOptions[0].getAttribute('data-max'));
                const equipoNombre = selectEquipo.selectedOptions[0].textContent.split(' (')[0];

                if (!equipoId || !cantidad) {
                    alert('Selecciona un equipo y cantidad válida');
                    return;
                }

                if (cantidad > maxCantidad) {
                    alert(`No hay suficiente cantidad disponible. Máximo: ${maxCantidad}`);
                    return;
                }

                // Verificar si el equipo ya está en la lista
                const equipoExistente = equiposSeleccionados.find(e => e.id_equipo == equipoId);
                if (equipoExistente) {
                    equipoExistente.cantidad += cantidad;
                } else {
                    equiposSeleccionados.push({
                        id_equipo: equipoId,
                        nombre_equipo: equipoNombre,
                        cantidad: cantidad
                    });
                }

                actualizarListaEquipos();
                selectEquipo.value = '';
                inputCantidad.value = 1;
            });

            function actualizarListaEquipos() {
                const listaEquipos = document.getElementById('lista-equipos');
                listaEquipos.innerHTML = '';

                if (equiposSeleccionados.length === 0) {
                    listaEquipos.innerHTML = '<p class="text-muted text-center mb-0">No hay equipos seleccionados</p>';
                    return;
                }

                equiposSeleccionados.forEach((equipo, index) => {
                    const div = document.createElement('div');
                    div.className = 'd-flex justify-content-between align-items-center mb-2 p-2 border rounded';
                    div.innerHTML = `
                        <div>
                            <strong>${equipo.nombre_equipo}</strong>
                            <br><small class="text-muted">Cantidad: ${equipo.cantidad}</small>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger remover-equipo" data-index="${index}">
                            <i class="bi bi-trash"></i>
                        </button>
                    `;
                    listaEquipos.appendChild(div);
                });

                // Agregar event listeners para remover equipos
                document.querySelectorAll('.remover-equipo').forEach(button => {
                    button.addEventListener('click', function() {
                        const index = parseInt(this.getAttribute('data-index'));
                        equiposSeleccionados.splice(index, 1);
                        actualizarListaEquipos();
                    });
                });
            }

            // Confirmar préstamo
            document.getElementById('btn-confirmar-prestamo').addEventListener('click', function() {
                if (equiposSeleccionados.length === 0) {
                    alert('Debe agregar al menos un equipo');
                    return;
                }

                const formData = new FormData(document.getElementById('form-prestamo-presencial'));
                formData.append('equipos', JSON.stringify(equiposSeleccionados));

                fetch('/prestlab/public/index.php?controller=prestamo&action=crearPrestamoPresencial', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al crear el préstamo');
                });
            });

            // ✅ PASO 3: JAVASCRIPT PARA CARGAR Y EDITAR PRÉSTAMOS
            const modalEditar = new bootstrap.Modal(document.getElementById('modalEditarPrestamo'));
            const nuevosEquipos = [];

            // Cargar datos al modal de edición
            document.querySelectorAll('.editar-prestamo').forEach(button => {
                button.addEventListener('click', function () {
                    const id = this.dataset.prestamoId;
                    const codigo = this.dataset.prestamoCodigo;
                    const fecha = this.dataset.fechaLimite;

                    document.getElementById('edit_id_prestamo').value = id;
                    document.getElementById('edit_codigo_prestamo').value = codigo;
                    document.getElementById('edit_fecha_limite').value = fecha;

                    // Limpiar lista de nuevos equipos
                    nuevosEquipos.length = 0;
                    document.getElementById('lista-nuevos-equipos').innerHTML = '';

                    // 🔧 PASO 2: Cargar equipos actuales con controles de edición
                    cargarEquiposActuales(id);

                    modalEditar.show();
                });
            });

            // Agregar equipo en modal de edición
            document.getElementById('btn-agregar-equipo-editar').addEventListener('click', function() {
                const selectEquipo = document.getElementById('select-equipo-agregar');
                const equipoId = selectEquipo.value;
                const maxCantidad = parseInt(selectEquipo.selectedOptions[0].getAttribute('data-max'));
                const equipoNombre = selectEquipo.selectedOptions[0].textContent.split(' (')[0];

                if (!equipoId) {
                    alert('Selecciona un equipo válido');
                    return;
                }

                // Verificar si el equipo ya está en la lista
                const equipoExistente = nuevosEquipos.find(e => e.id_equipo == equipoId);
                if (equipoExistente) {
                    if (equipoExistente.cantidad >= maxCantidad) {
                        alert(`No hay suficiente cantidad disponible. Máximo: ${maxCantidad}`);
                        return;
                    }
                    equipoExistente.cantidad += 1;
                } else {
                    nuevosEquipos.push({
                        id_equipo: equipoId,
                        nombre_equipo: equipoNombre,
                        cantidad: 1
                    });
                }

                actualizarListaNuevosEquipos();
                selectEquipo.value = '';
            });

            function actualizarListaNuevosEquipos() {
                const listaNuevosEquipos = document.getElementById('lista-nuevos-equipos');
                listaNuevosEquipos.innerHTML = '';

                if (nuevosEquipos.length === 0) {
                    return;
                }

                listaNuevosEquipos.innerHTML = '<h6>Equipos a agregar:</h6>';
                nuevosEquipos.forEach((equipo, index) => {
                    const div = document.createElement('div');
                    div.className = 'd-flex justify-content-between align-items-center mb-2 p-2 border rounded';
                    div.innerHTML = `
                        <div>
                            <strong>${equipo.nombre_equipo}</strong>
                            <br><small class="text-muted">Cantidad: ${equipo.cantidad}</small>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger remover-nuevo-equipo" data-index="${index}">
                            <i class="bi bi-trash"></i>
                        </button>
                    `;
                    listaNuevosEquipos.appendChild(div);
                });

                // Agregar event listeners para remover nuevos equipos
                document.querySelectorAll('.remover-nuevo-equipo').forEach(button => {
                    button.addEventListener('click', function() {
                        const index = parseInt(this.getAttribute('data-index'));
                        nuevosEquipos.splice(index, 1);
                        actualizarListaNuevosEquipos();
                    });
                });
            }

            // Guardar cambios del préstamo
            document.getElementById('btn-guardar-cambios').addEventListener('click', function () {
                const formData = new FormData(document.getElementById('form-editar-prestamo'));
                
                // Agregar nuevos equipos al FormData
                if (nuevosEquipos.length > 0) {
                    formData.append('nuevos_equipos', JSON.stringify(nuevosEquipos));
                }

                fetch('/prestlab/public/index.php?controller=prestamo&action=actualizarPrestamo', {
                    method: 'POST',
                    body: formData
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            alert(data.message);
                            location.reload();
                        } else {
                            alert('Error: ' + data.error);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error al actualizar el préstamo');
                    });
            });
        });

        // 🔧 PASO 2: Cargar equipos actuales del préstamo
        function cargarEquiposActuales(idPrestamo) {
            fetch(`/prestlab/public/index.php?controller=prestamo&action=obtenerDetallePrestamo&id=${idPrestamo}`)
                .then(res => res.json())
                .then(data => {
                    const container = document.getElementById('equipos-actuales');
                    if (!data.success || data.data.length === 0) {
                        container.innerHTML = '<p class="text-muted mb-0">No hay equipos asignados</p>';
                        return;
                    }

                    let html = '<ul class="list-group">';
                    data.data.forEach(item => {
                        html += `
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>${item.nombre_equipo}</strong><br>
                                    <small class="text-muted">Código: ${item.id_equipo}</small>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <input type="number" class="form-control form-control-sm" 
                                           value="${item.cantidad}" min="1" max="10" 
                                           style="width: 80px;" 
                                           data-id="${item.id_equipo}" 
                                           onchange="actualizarCantidad(${idPrestamo}, ${item.id_equipo}, this.value)">
                                    <button class="btn btn-sm btn-outline-danger" 
                                            onclick="eliminarEquipo(${idPrestamo}, ${item.id_equipo})">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </li>`;
                    });
                    html += '</ul>';
                    container.innerHTML = html;
                });
        }

        // 🔧 PASO 3: Funciones para actualizar cantidad y eliminar equipo
        function actualizarCantidad(idPrestamo, idEquipo, nuevaCantidad) {
            if (!confirm('¿Actualizar cantidad?')) return;

            const formData = new FormData();
            formData.append('id_prestamo', idPrestamo);
            formData.append('id_equipo', idEquipo);
            formData.append('cantidad', nuevaCantidad);

            fetch('/prestlab/public/index.php?controller=prestamo&action=actualizarCantidadEquipo', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Cantidad actualizada');
                } else {
                    alert('Error: ' + data.error);
                }
            });
        }

        function eliminarEquipo(idPrestamo, idEquipo) {
            if (!confirm('¿Eliminar este equipo del préstamo?')) return;

            const formData = new FormData();
            formData.append('id_prestamo', idPrestamo);
            formData.append('id_equipo', idEquipo);

            fetch('/prestlab/public/index.php?controller=prestamo&action=eliminarEquipoDePrestamo', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Equipo eliminado');
                    cargarEquiposActuales(idPrestamo); // Recargar lista
                } else {
                    alert('Error: ' + data.error);
                }
            });
        }
    </script>
</body>
</html>