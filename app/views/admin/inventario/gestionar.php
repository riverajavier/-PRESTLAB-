<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PRESTLAB - Gestión de Inventario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .navbar-custom { background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%); }
        .sidebar { background-color: #2c3e50; min-height: calc(100vh - 76px); padding: 0; }
        .sidebar .nav-link { color: rgba(255,255,255,0.8); padding: 15px 20px; border-left: 3px solid transparent; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background-color: rgba(255,255,255,0.1); border-left-color: #3498db; color: white; }
        .stat-card { background: white; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; }
        .equipment-card { border: 1px solid #e0e0e0; border-radius: 8px; padding: 15px; margin-bottom: 15px; background: white; }
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
                    <a class="nav-link active" href="/prestlab/public/index.php?controller=inventario&action=gestionarInventario"><i class="bi bi-box-seam"></i> Inventario</a>
                    <a class="nav-link" href="/prestlab/public/index.php?controller=prestamo&action=gestionarPrestamos"><i class="bi bi-clipboard-check"></i> Préstamos</a>
                    <a class="nav-link" href="/prestlab/public/index.php?controller=devolucion&action=gestionarDevoluciones"><i class="bi bi-arrow-return-left"></i> Devoluciones</a>
                    <a class="nav-link" href="/prestlab/public/index.php?controller=usuario&action=gestionarUsuarios"><i class="bi bi-people"></i> Usuarios</a>
                </nav>
            </div>

            <!-- Contenido principal -->
            <div class="col-md-9 col-lg-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2>Gestión de Inventario</h2>
                        <p class="text-muted">Administra el inventario de equipos</p>
                    </div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearEquipo">
                        <i class="bi bi-plus-circle"></i> Agregar Equipo
                    </button>
                </div>

                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" action="">
                            <input type="hidden" name="controller" value="inventario">
                            <input type="hidden" name="action" value="gestionarInventario">
                            <div class="row">
                                <div class="col-md-6">
                                    <input type="text" class="form-control" name="nombre" placeholder="Buscar por nombre de equipo..." value="<?php echo htmlspecialchars($filtros_actuales['nombre'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4">
                                    <select class="form-select" name="estado">
                                        <option value="">Todos los estados</option>
                                        <?php foreach ($estados_equipo as $estado): ?>
                                            <option value="<?php echo $estado['id_estado_equipo']; ?>" <?php echo (isset($filtros_actuales['estado']) && $filtros_actuales['estado'] == $estado['id_estado_equipo']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($estado['nombre_estado']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel"></i> Filtrar</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Lista de equipos -->
                <div class="row">
                    <?php foreach ($equipos as $equipo): ?>
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="equipment-card">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($equipo['nombre_equipo']); ?></h6>
                                        <p class="text-muted mb-0 small"><?php echo htmlspecialchars($equipo['descripcion'] ?? 'Sin descripción'); ?></p>
                                    </div>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                            <i class="bi bi-gear"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item editar-equipo" href="#" 
                                                    data-equipo-id="<?= $equipo['id_equipo'] ?>"
                                                    data-equipo-nombre="<?= htmlspecialchars($equipo['nombre_equipo']) ?>"
                                                    data-equipo-descripcion="<?= htmlspecialchars($equipo['descripcion'] ?? '') ?>"
                                                    data-equipo-fecha="<?= $equipo['fecha_adquisicion'] ?>"
                                                    data-equipo-estado="<?= $equipo['id_estado_equipo'] ?>"
                                                    data-equipo-total="<?= $equipo['cantidad_total'] ?>"
                                                    data-equipo-disponible="<?= $equipo['cantidad_disponible'] ?>"
                                                    data-equipo-imagen="<?= htmlspecialchars($equipo['imagen_url'] ?? '') ?>">
                                                    <i class="bi bi-pencil"></i> Editar
                                                </a>
                                            </li>
                                            <li><a class="dropdown-item eliminar-equipo" href="#" data-equipo-id="<?php echo $equipo['id_equipo']; ?>" data-equipo-nombre="<?php echo htmlspecialchars($equipo['nombre_equipo']); ?>"><i class="bi bi-trash"></i> Eliminar</a></li>
                                        </ul>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <span class="badge bg-<?php 
                                        switch($equipo['id_estado_equipo']) {
                                            case 1: echo 'success'; break;
                                            case 2: echo 'warning'; break;
                                            case 3: echo 'danger'; break;
                                            case 4: echo 'secondary'; break;
                                            default: echo 'light';
                                        }
                                    ?>">
                                        <?php echo htmlspecialchars($equipo['nombre_estado']); ?>
                                    </span>
                                </div>

                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="bi bi-box"></i> 
                                        <?php echo $equipo['cantidad_disponible']; ?> / <?php echo $equipo['cantidad_total']; ?> disp.
                                    </small>
                                    <small class="text-muted">
                                        <i class="bi bi-calendar"></i> 
                                        <?php echo date('d/m/Y', strtotime($equipo['fecha_adquisicion'])); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (empty($equipos)): ?>
                    <div class="text-center py-4">
                        <i class="bi bi-box-seam" style="font-size: 3rem; color: #6c757d;"></i>
                        <h5 class="mt-3">No se encontraron equipos</h5>
                        <p class="text-muted">Intenta con otros filtros de búsqueda o agrega nuevos equipos</p>
                    </div>
                <?php endif; ?>

                <!-- Paginación -->
                <?php if ($total_paginas > 1): ?>
                    <nav aria-label="Paginación" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                <li class="page-item <?php echo $i == $pagina_actual ? 'active' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge(
                                        ['controller' => 'inventario', 'action' => 'gestionarInventario'], 
                                        $filtros_actuales, 
                                        ['pagina' => $i]
                                    )); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Crear Equipo -->
    <div class="modal fade" id="modalCrearEquipo" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Agregar Nuevo Equipo</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="form-crear-equipo">
                        <div class="mb-3">
                            <label class="form-label">Nombre del Equipo</label>
                            <input type="text" class="form-control" name="nombre" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea class="form-control" name="descripcion" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Fecha de Adquisición</label>
                            <input type="date" class="form-control" name="fecha_adquisicion" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Estado</label>
                            <select class="form-select" name="estado" required>
                                <?php foreach ($estados_equipo as $estado): ?>
                                    <option value="<?php echo $estado['id_estado_equipo']; ?>"><?php echo htmlspecialchars($estado['nombre_estado']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Cantidad Total</label>
                                <input type="number" class="form-control" name="cantidad_total" min="0" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Cantidad Disponible</label>
                                <input type="number" class="form-control" name="cantidad_disponible" min="0" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">URL de Imagen (opcional)</label>
                            <input type="url" class="form-control" name="imagen_url" placeholder="https://...">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btn-crear-equipo">Crear Equipo</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Editar Equipo -->
    <div class="modal fade" id="modalEditarEquipo" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header bg-warning text-dark">
            <h5 class="modal-title">Editar Equipo</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <form id="form-editar-equipo" enctype="multipart/form-data">
              <input type="hidden" id="edit_id_equipo" name="id_equipo">
              <input type="hidden" id="imagen_url_existente" name="imagen_url_existente">

              <div class="mb-3">
                <label>Nombre del Equipo</label>
                <input type="text" class="form-control" id="edit_nombre" name="nombre" required>
              </div>

              <div class="mb-3">
                <label>Descripción</label>
                <textarea class="form-control" id="edit_descripcion" name="descripcion" rows="3"></textarea>
              </div>

              <div class="row">
                <div class="col-md-4 mb-3">
                  <label>Fecha de Adquisición</label>
                  <input type="date" class="form-control" id="edit_fecha_adquisicion" name="fecha_adquisicion" required>
                </div>
                <div class="col-md-4 mb-3">
                  <label>Estado</label>
                  <select class="form-select" id="edit_estado" name="estado" required>
                    <?php foreach ($estados_equipo as $estado): ?>
                      <option value="<?= $estado['id_estado_equipo'] ?>"><?= htmlspecialchars($estado['nombre_estado']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-4 mb-3">
                  <label>Imagen del Equipo</label>
                  <input type="file" class="form-control" name="imagen" accept="image/*">
                  <div class="form-text">Deja vacío para mantener la imagen actual.</div>
                </div>
              </div>

              <div class="row">
                <div class="col-md-6 mb-3">
                  <label>Cantidad Total</label>
                  <input type="number" class="form-control" id="edit_cantidad_total" name="cantidad_total" min="0" required>
                </div>
                <div class="col-md-6 mb-3">
                  <label>Cantidad Disponible</label>
                  <input type="number" class="form-control" id="edit_cantidad_disponible" name="cantidad_disponible" min="0" required>
                </div>
              </div>

              <div class="mb-3">
                <label>Imagen Actual</label><br>
                <img id="imagen_preview" src="" alt="Imagen actual" style="max-width: 200px; border-radius: 8px;">
              </div>
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
            const modalEditar = new bootstrap.Modal(document.getElementById('modalEditarEquipo'));

            // Crear equipo
            document.getElementById('btn-crear-equipo').addEventListener('click', function() {
                const formData = new FormData(document.getElementById('form-crear-equipo'));
                
                fetch('/prestlab/public/index.php?controller=inventario&action=crearEquipo', {
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
                    alert('Error al crear el equipo');
                });
            });

            // Eliminar equipo
            document.querySelectorAll('.eliminar-equipo').forEach(button => {
                button.addEventListener('click', function() {
                    const equipoId = this.getAttribute('data-equipo-id');
                    const equipoNombre = this.getAttribute('data-equipo-nombre');
                    
                    if (confirm(`¿Estás seguro de eliminar el equipo "${equipoNombre}"?`)) {
                        const formData = new FormData();
                        formData.append('id_equipo', equipoId);
                        
                        fetch('/prestlab/public/index.php?controller=inventario&action=eliminarEquipo', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('Equipo eliminado exitosamente');
                                location.reload();
                            } else {
                                alert('Error: ' + data.error);
                            }
                        });
                    }
                });
            });

            // ✅ AGREGADO: Cargar datos al modal de edición
            document.querySelectorAll('.editar-equipo').forEach(button => {
                button.addEventListener('click', function () {
                    document.getElementById('edit_id_equipo').value = this.dataset.equipoId;
                    document.getElementById('edit_nombre').value = this.dataset.equipoNombre;
                    document.getElementById('edit_descripcion').value = this.dataset.equipoDescripcion;
                    document.getElementById('edit_fecha_adquisicion').value = this.dataset.equipoFecha;
                    document.getElementById('edit_estado').value = this.dataset.equipoEstado;
                    document.getElementById('edit_cantidad_total').value = this.dataset.equipoTotal;
                    document.getElementById('edit_cantidad_disponible').value = this.dataset.equipoDisponible;
                    document.getElementById('imagen_url_existente').value = this.dataset.equipoImagen;

                    const imgPreview = document.getElementById('imagen_preview');
                    imgPreview.src = this.dataset.equipoImagen || '/prestlab/public/uploads/equipos/default.png';

                    modalEditar.show();
                });
            });

            // ✅ AGREGADO: Enviar formulario de edición
            document.getElementById('btn-guardar-cambios').addEventListener('click', function () {
                const form = document.getElementById('form-editar-equipo');
                const formData = new FormData(form);

                fetch('/prestlab/public/index.php?controller=inventario&action=editarEquipo', {
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
                });
            });
        });
    </script>
</body>
</html>