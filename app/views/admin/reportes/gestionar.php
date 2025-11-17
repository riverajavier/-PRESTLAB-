<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PRESTLAB - Sistema de Reportes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .navbar-custom { background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%); }
        .sidebar { background-color: #2c3e50; min-height: calc(100vh - 76px); padding: 0; }
        .sidebar .nav-link { color: rgba(255,255,255,0.8); padding: 15px 20px; border-left: 3px solid transparent; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background-color: rgba(255,255,255,0.1); border-left-color: #3498db; color: white; }
        .stat-card { background: white; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; }
        .alert-card { border-left: 4px solid #ffc107; background: #fffbf0; }
        .warning-card { border-left: 4px solid #dc3545; background: #f8f9fa; }
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
                    <a class="nav-link" href="/prestlab/public/index.php?controller=prestamo&action=gestionarPrestamos"><i class="bi bi-clipboard-check"></i> Préstamos</a>
                    <a class="nav-link" href="/prestlab/public/index.php?controller=devolucion&action=gestionarDevoluciones"><i class="bi bi-arrow-return-left"></i> Devoluciones</a>
                    <a class="nav-link" href="/prestlab/public/index.php?controller=usuario&action=gestionarUsuarios"><i class="bi bi-people"></i> Usuarios</a>
                    <a class="nav-link active" href="/prestlab/public/index.php?controller=reporte&action=gestionarReportes"><i class="bi bi-graph-up"></i> Reportes</a>
                </nav>
            </div>

            <!-- Contenido principal -->
            <div class="col-md-9 col-lg-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2>Sistema de Reportes</h2>
                        <p class="text-muted">Reportes y estadísticas del sistema</p>
                    </div>
                </div>

                <!-- Alertas y Notificaciones -->
                <div class="row mb-4">
                    <!-- Préstamos próximos a vencer -->
                    <?php if (!empty($prestamos_proximos)): ?>
                        <div class="col-md-6">
                            <div class="card alert-card">
                                <div class="card-header bg-warning text-dark">
                                    <h6 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Préstamos por Vencer</h6>
                                </div>
                                <div class="card-body">
                                    <?php foreach ($prestamos_proximos as $prestamo): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div>
                                                <small><strong><?php echo htmlspecialchars($prestamo['nombre'] . ' ' . $prestamo['apellido']); ?></strong></small>
                                                <br><small class="text-muted">Vence en <?php echo $prestamo['dias_restantes']; ?> días</small>
                                            </div>
                                            <span class="badge bg-warning"><?php echo $prestamo['codigo_prestamo']; ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Equipos con baja disponibilidad -->
                    <?php if (!empty($equipos_baja_disponibilidad)): ?>
                        <div class="col-md-6">
                            <div class="card warning-card">
                                <div class="card-header bg-danger text-white">
                                    <h6 class="mb-0"><i class="bi bi-box"></i> Equipos con Baja Disponibilidad</h6>
                                </div>
                                <div class="card-body">
                                    <?php foreach ($equipos_baja_disponibilidad as $equipo): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div>
                                                <small><strong><?php echo htmlspecialchars($equipo['nombre_equipo']); ?></strong></small>
                                                <br><small class="text-muted"><?php echo $equipo['cantidad_disponible']; ?> disponibles</small>
                                            </div>
                                            <span class="badge bg-danger"><?php echo $equipo['porcentaje_disponible']; ?>%</span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Estadísticas Generales -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <i class="bi bi-people" style="font-size: 2rem; color: #667eea;"></i>
                            <h3 class="mt-2 mb-0"><?php echo $estadisticas['usuarios_activos']; ?></h3>
                            <p class="text-muted mb-0">Usuarios Activos</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <i class="bi bi-box-seam" style="font-size: 2rem; color: #28a745;"></i>
                            <h3 class="mt-2 mb-0"><?php echo $estadisticas['equipos_disponibles']; ?></h3>
                            <p class="text-muted mb-0">Equipos Disponibles</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <i class="bi bi-clipboard-check" style="font-size: 2rem; color: #ffc107;"></i>
                            <h3 class="mt-2 mb-0"><?php echo $estadisticas['prestamos_activos']; ?></h3>
                            <p class="text-muted mb-0">Préstamos Activos</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <i class="bi bi-exclamation-triangle" style="font-size: 2rem; color: #dc3545;"></i>
                            <h3 class="mt-2 mb-0"><?php echo $estadisticas['prestamos_vencidos']; ?></h3>
                            <p class="text-muted mb-0">Préstamos Vencidos</p>
                        </div>
                    </div>
                </div>

                <!-- Generador de Reportes -->
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-graph-up"></i> Generar Reportes</h5>
                    </div>
                    <div class="card-body">
                        <!-- Pestañas -->
                        <ul class="nav nav-tabs mb-4" id="reportesTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="inventario-tab" data-bs-toggle="tab" data-bs-target="#inventario" type="button">
                                    <i class="bi bi-box-seam"></i> Inventario
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="prestamos-tab" data-bs-toggle="tab" data-bs-target="#prestamos" type="button">
                                    <i class="bi bi-clipboard-check"></i> Préstamos
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="usuarios-tab" data-bs-toggle="tab" data-bs-target="#usuarios" type="button">
                                    <i class="bi bi-people"></i> Usuarios
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content" id="reportesTabsContent">
                            <!-- Reporte de Inventario -->
                            <div class="tab-pane fade show active" id="inventario" role="tabpanel">
                                <form id="form-reporte-inventario">
                                    <div class="row mb-3">
                                        <div class="col-md-4">
                                            <label class="form-label">Estado del Equipo</label>
                                            <select class="form-select" name="estado">
                                                <option value="">Todos los estados</option>
                                                <option value="1">Disponible</option>
                                                <option value="2">Prestado</option>
                                                <option value="3">Mantenimiento</option>
                                                <option value="4">Dado de Baja</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Fecha Desde</label>
                                            <input type="date" class="form-control" name="fecha_desde">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Fecha Hasta</label>
                                            <input type="date" class="form-control" name="fecha_hasta">
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-primary" onclick="generarReporte('inventario')">
                                            <i class="bi bi-eye"></i> Ver Reporte
                                        </button>
                                        <button type="button" class="btn btn-success" onclick="exportarPDF('inventario')">
                                            <i class="bi bi-file-pdf"></i> Exportar PDF
                                        </button>
                                        <button type="button" class="btn btn-success" onclick="exportarExcel('inventario')">
                                            <i class="bi bi-file-excel"></i> Exportar Excel
                                        </button>
                                    </div>
                                </form>
                                <div id="resultado-inventario" class="mt-4"></div>
                            </div>

                            <!-- Reporte de Préstamos -->
                            <div class="tab-pane fade" id="prestamos" role="tabpanel">
                                <form id="form-reporte-prestamos">
                                    <div class="row mb-3">
                                        <div class="col-md-4">
                                            <label class="form-label">Estado del Préstamo</label>
                                            <select class="form-select" name="estado">
                                                <option value="">Todos los estados</option>
                                                <option value="1">Activo</option>
                                                <option value="2">Vencido</option>
                                                <option value="3">Completado</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Fecha Desde</label>
                                            <input type="date" class="form-control" name="fecha_desde">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Fecha Hasta</label>
                                            <input type="date" class="form-control" name="fecha_hasta">
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-primary" onclick="generarReporte('prestamos')">
                                            <i class="bi bi-eye"></i> Ver Reporte
                                        </button>
                                        <button type="button" class="btn btn-success" onclick="exportarPDF('prestamos')">
                                            <i class="bi bi-file-pdf"></i> Exportar PDF
                                        </button>
                                        <button type="button" class="btn btn-success" onclick="exportarExcel('prestamos')">
                                            <i class="bi bi-file-excel"></i> Exportar Excel
                                        </button>
                                    </div>
                                </form>
                                <div id="resultado-prestamos" class="mt-4"></div>
                            </div>

                            <!-- Reporte de Usuarios -->
                            <div class="tab-pane fade" id="usuarios" role="tabpanel">
                                <form id="form-reporte-usuarios">
                                    <div class="row mb-3">
                                        <div class="col-md-3">
                                            <label class="form-label">Rol</label>
                                            <select class="form-select" name="rol">
                                                <option value="">Todos los roles</option>
                                                <option value="1">Administrador</option>
                                                <option value="2">Usuario</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Estado</label>
                                            <select class="form-select" name="estado">
                                                <option value="">Todos los estados</option>
                                                <option value="activo">Activo</option>
                                                <option value="inactivo">Inactivo</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Fecha Desde</label>
                                            <input type="date" class="form-control" name="fecha_desde">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Fecha Hasta</label>
                                            <input type="date" class="form-control" name="fecha_hasta">
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-primary" onclick="generarReporte('usuarios')">
                                            <i class="bi bi-eye"></i> Ver Reporte
                                        </button>
                                        <button type="button" class="btn btn-success" onclick="exportarPDF('usuarios')">
                                            <i class="bi bi-file-pdf"></i> Exportar PDF
                                        </button>
                                        <button type="button" class="btn btn-success" onclick="exportarExcel('usuarios')">
                                            <i class="bi bi-file-excel"></i> Exportar Excel
                                        </button>
                                    </div>
                                </form>
                                <div id="resultado-usuarios" class="mt-4"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function generarReporte(tipo) {
            const form = document.getElementById(`form-reporte-${tipo}`);
            const formData = new FormData(form);
            const params = new URLSearchParams(formData);
            
            fetch(`/prestlab/public/index.php?controller=reporte&action=generarReporte${tipo.charAt(0).toUpperCase() + tipo.slice(1)}&${params}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        mostrarResultado(tipo, data.reporte);
                    } else {
                        alert('Error al generar el reporte');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al generar el reporte');
                });
        }

        function mostrarResultado(tipo, datos) {
            const contenedor = document.getElementById(`resultado-${tipo}`);
            
            if (datos.length === 0) {
                contenedor.innerHTML = '<div class="alert alert-info">No se encontraron resultados</div>';
                return;
            }

            let html = '<div class="table-responsive"><table class="table table-striped"><thead><tr>';
            
            // Encabezados dinámicos
            if (tipo === 'inventario') {
                html += '<th>Equipo</th><th>Estado</th><th>Total</th><th>Disponible</th><th>Prestados</th><th>% Disp.</th>';
            } else if (tipo === 'prestamos') {
                html += '<th>Código</th><th>Usuario</th><th>Estado</th><th>Fecha Préstamo</th><th>Fecha Límite</th><th>Equipos</th>';
            } else if (tipo === 'usuarios') {
                html += '<th>Usuario</th><th>Rol</th><th>Estado</th><th>Total Préstamos</th><th>Activos</th><th>Vencidos</th>';
            }
            
            html += '</tr></thead><tbody>';

            // Datos
            datos.forEach(item => {
                html += '<tr>';
                if (tipo === 'inventario') {
                    html += `<td>${item.nombre_equipo}</td>
                            <td><span class="badge bg-secondary">${item.nombre_estado}</span></td>
                            <td>${item.cantidad_total}</td>
                            <td>${item.cantidad_disponible}</td>
                            <td>${item.cantidad_prestada}</td>
                            <td>${item.porcentaje_disponible}%</td>`;
                } else if (tipo === 'prestamos') {
                    html += `<td>${item.codigo_prestamo}</td>
                            <td>${item.nombre} ${item.apellido}</td>
                            <td><span class="badge bg-${item.estado_actual === 'Vencido' ? 'danger' : 'success'}">${item.estado_actual}</span></td>
                            <td>${item.fecha_prestamo}</td>
                            <td>${item.fecha_limite_devolucion}</td>
                            <td>${item.total_equipos}</td>`;
                } else if (tipo === 'usuarios') {
                    html += `<td>${item.nombre} ${item.apellido}</td>
                            <td><span class="badge bg-${item.id_rol === 1 ? 'danger' : 'primary'}">${item.nombre_rol}</span></td>
                            <td><span class="badge bg-${item.estado === 'activo' ? 'success' : 'secondary'}">${item.estado}</span></td>
                            <td>${item.total_prestamos}</td>
                            <td>${item.prestamos_activos}</td>
                            <td>${item.prestamos_vencidos}</td>`;
                }
                html += '</tr>';
            });

            html += '</tbody></table></div>';
            contenedor.innerHTML = html;
        }

        function exportarPDF(tipo) {
            const form = document.getElementById(`form-reporte-${tipo}`);
            const formData = new FormData(form);
            const params = new URLSearchParams(formData);
            
            window.open(`/prestlab/public/index.php?controller=reporte&action=exportarPDF&tipo=${tipo}&${params}`, '_blank');
        }

        function exportarExcel(tipo) {
            const form = document.getElementById(`form-reporte-${tipo}`);
            const formData = new FormData(form);
            const params = new URLSearchParams(formData);
            
            window.open(`/prestlab/public/index.php?controller=reporte&action=exportarExcel&tipo=${tipo}&${params}`, '_blank');
        }
    </script>
</body>
</html>