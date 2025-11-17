<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PRESTLAB - Gestión de Devoluciones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar-custom {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
        }
        .sidebar {
            background-color: #2c3e50;
            min-height: calc(100vh - 76px);
            padding: 0;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 15px 20px;
            border-left: 3px solid transparent;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background-color: rgba(255,255,255,0.1);
            border-left-color: #3498db;
            color: white;
        }
        .badge-estado {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
        }
        .badge-activo {
            background-color: #28a745;
            color: white;
        }
        .badge-vencido {
            background-color: #dc3545;
            color: white;
        }
        .prestamo-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <!-- Navbar Admin -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="bi bi-person-badge"></i> PRESTLAB - Admin
            </a>
            <div class="d-flex align-items-center">
                <span class="navbar-text me-3">
                    <i class="bi bi-person-circle"></i> <?php echo Session::get('user_nombre'); ?>
                </span>
                <a href="/prestlab/public/index.php?action=logout" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-box-arrow-right"></i> Salir
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <nav class="nav flex-column">
                    <a class="nav-link" href="/prestlab/public/admin/dashboard.php">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                    <a class="nav-link" href="/prestlab/public/index.php?controller=inventario&action=<?php echo Session::getUserRole() == 1 ? 'gestionarInventario' : 'consultar'; ?>">
                        <i class="bi bi-box-seam"></i> Inventario
                    </a>
                    <a class="nav-link" href="/prestlab/public/index.php?controller=prestamo&action=gestionarPrestamos">
                        <i class="bi bi-clipboard-check"></i> Préstamos
                    </a>
                    <a class="nav-link active" href="/prestlab/public/index.php?controller=devolucion&action=gestionarDevoluciones">
                        <i class="bi bi-arrow-return-left"></i> Devoluciones
                    </a>
                    <a class="nav-link" href="/prestlab/public/index.php?controller=usuario&action=gestionarUsuarios">
                        <i class="bi bi-people"></i> Usuarios
                    </a>
                    <a class="nav-link" href="/prestlab/public/index.php?controller=reporte&action=gestionarReportes">
                        <i class="bi bi-graph-up"></i> Reportes
                    </a>
                </nav>
            </div>

            <!-- Contenido principal -->
            <div class="col-md-9 col-lg-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2>Gestión de Devoluciones</h2>
                        <p class="text-muted">Procesa devoluciones y revisa el estado de los equipos</p>
                    </div>
                </div>

                <!-- Pestañas -->
                <ul class="nav nav-tabs mb-4" id="devolucionesTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="pendientes-tab" data-bs-toggle="tab" data-bs-target="#pendientes" type="button">
                            <i class="bi bi-clock"></i> Pendientes de Devolución
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="activos-tab" data-bs-toggle="tab" data-bs-target="#activos" type="button">
                            <i class="bi bi-list-check"></i> Todos los Préstamos Activos
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="devolucionesTabsContent">
                    <!-- Pestaña 1: Pendientes de Devolución -->
                    <div class="tab-pane fade show active" id="pendientes" role="tabpanel">
                        <?php if (empty($prestamos_para_devolucion)): ?>
                            <div class="alert alert-success text-center">
                                <i class="bi bi-check-circle" style="font-size: 2rem;"></i>
                                <h4 class="mt-3">¡No hay devoluciones pendientes!</h4>
                                <p class="mb-0">Todos los equipos han sido devueltos y procesados.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Código</th>
                                            <th>Usuario</th>
                                            <th>Equipo</th>
                                            <th>Cantidad</th>
                                            <th>Fecha Límite</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($prestamos_para_devolucion as $prestamo): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($prestamo['codigo_prestamo']); ?></strong>
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars($prestamo['nombre'] . ' ' . $prestamo['apellido']); ?><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($prestamo['correo']); ?></small>
                                                </td>
                                                <td><?php echo htmlspecialchars($prestamo['nombre_equipo']); ?></td>
                                                <td><?php echo $prestamo['cantidad']; ?></td>
                                                <td>
                                                    <?php echo date('d/m/Y', strtotime($prestamo['fecha_limite_devolucion'])); ?>
                                                    <?php if (strtotime($prestamo['fecha_limite_devolucion']) < strtotime('today')): ?>
                                                        <br><small class="text-danger">¡Vencido!</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $badge_class = $prestamo['id_estado_prestamo'] == 1 ? 'badge-activo' : 'badge-vencido';
                                                    ?>
                                                    <span class="badge-estado <?php echo $badge_class; ?>">
                                                        <?php echo htmlspecialchars($prestamo['estado_prestamo']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-success procesar-devolucion" 
                                                            data-prestamo-id="<?php echo $prestamo['id_prestamo']; ?>"
                                                            data-equipo-id="<?php echo $prestamo['id_equipo']; ?>"
                                                            data-equipo-nombre="<?php echo htmlspecialchars($prestamo['nombre_equipo']); ?>"
                                                            data-usuario-nombre="<?php echo htmlspecialchars($prestamo['nombre'] . ' ' . $prestamo['apellido']); ?>">
                                                        <i class="bi bi-check-circle"></i> Procesar Devolución
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Pestaña 2: Todos los Préstamos Activos -->
                    <div class="tab-pane fade" id="activos" role="tabpanel">
                        <?php if (empty($prestamos_activos)): ?>
                            <div class="alert alert-info text-center">
                                <i class="bi bi-info-circle" style="font-size: 2rem;"></i>
                                <h4 class="mt-3">No hay préstamos activos</h4>
                                <p class="mb-0">Todos los préstamos han sido completados o devueltos.</p>
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

                                            <div class="mb-3">
                                                <small class="text-muted">Equipos:</small>
                                                <p class="mb-1"><?php echo htmlspecialchars($prestamo['nombres_equipos']); ?></p>
                                                <small class="text-muted">
                                                    Total equipos: <?php echo $prestamo['total_equipos']; ?>
                                                </small>
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

    <!-- Modal para procesar devolución -->
    <div class="modal fade" id="modalProcesarDevolucion" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Procesar Devolución</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="info-devolucion" class="alert alert-info">
                        <!-- Información se cargará aquí -->
                    </div>
                    <form id="form-procesar-devolucion">
                        <input type="hidden" id="id_prestamo" name="id_prestamo">
                        <input type="hidden" id="id_equipo" name="id_equipo">
                        
                        <div class="mb-3">
                            <label for="id_estado_devolucion" class="form-label">Estado del Equipo</label>
                            <select class="form-select" id="id_estado_devolucion" name="id_estado_devolucion" required>
                                <option value="">Seleccionar estado...</option>
                                <?php foreach ($estados_devolucion as $estado): ?>
                                    <option value="<?php echo $estado['id_estado_devolucion']; ?>">
                                        <?php echo htmlspecialchars($estado['nombre_estado']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="observaciones" class="form-label">Observaciones de la Revisión</label>
                            <textarea class="form-control" id="observaciones" name="observaciones" rows="3" 
                                      placeholder="Describa el estado físico del equipo..."></textarea>
                            <div class="form-text">Obligatorio para equipos dañados, recomendado para otros estados.</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" id="btn-confirmar-devolucion">
                        <i class="bi bi-check-circle"></i> Confirmar Devolución
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modalDevolucion = new bootstrap.Modal(document.getElementById('modalProcesarDevolucion'));
            const formDevolucion = document.getElementById('form-procesar-devolucion');
            const infoDevolucion = document.getElementById('info-devolucion');
            const estadoSelect = document.getElementById('id_estado_devolucion');
            const observacionesTextarea = document.getElementById('observaciones');

            // Manejar clic en botones de procesar devolución
            document.querySelectorAll('.procesar-devolucion').forEach(button => {
                button.addEventListener('click', function() {
                    const prestamoId = this.getAttribute('data-prestamo-id');
                    const equipoId = this.getAttribute('data-equipo-id');
                    const equipoNombre = this.getAttribute('data-equipo-nombre');
                    const usuarioNombre = this.getAttribute('data-usuario-nombre');

                    // Actualizar modal con información
                    document.getElementById('id_prestamo').value = prestamoId;
                    document.getElementById('id_equipo').value = equipoId;
                    
                    infoDevolucion.innerHTML = `
                        <strong>Información de la Devolución:</strong><br>
                        <strong>Usuario:</strong> ${usuarioNombre}<br>
                        <strong>Equipo:</strong> ${equipoNombre}<br>
                        <strong>Préstamo ID:</strong> ${prestamoId}
                    `;

                    // Resetear formulario
                    formDevolucion.reset();
                    observacionesTextarea.disabled = false;
                    
                    // Mostrar modal
                    modalDevolucion.show();
                });
            });

            // Validar observaciones cuando el estado es "Dañado"
            estadoSelect.addEventListener('change', function() {
                if (this.value == '2') { // Dañado
                    observacionesTextarea.required = true;
                    observacionesTextarea.disabled = false;
                } else {
                    observacionesTextarea.required = false;
                }
            });

            // Confirmar devolución
            document.getElementById('btn-confirmar-devolucion').addEventListener('click', function() {
                const formData = new FormData(formDevolucion);

                // Validación adicional
                if (!estadoSelect.value) {
                    alert('Por favor, selecciona el estado del equipo.');
                    return;
                }

                if (estadoSelect.value == '2' && !observacionesTextarea.value.trim()) {
                    alert('Para equipos dañados es obligatorio agregar observaciones.');
                    return;
                }

                // Enviar solicitud
                fetch('/prestlab/public/index.php?controller=devolucion&action=procesarDevolucion', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        modalDevolucion.hide();
                        location.reload(); // Recargar para actualizar la lista
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al procesar la devolución.');
                });
            });
        });
    </script>
</body>
</html>