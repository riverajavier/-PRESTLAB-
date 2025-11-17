<?php
// Esta vista simula un PDF básico - en producción usarías una librería como TCPDF o Dompdf
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo; ?> - PRESTLAB</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { color: #2c3e50; margin: 0; }
        .header .subtitle { color: #7f8c8d; font-size: 14px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .table th { background-color: #34495e; color: white; padding: 10px; text-align: left; }
        .table td { padding: 8px; border-bottom: 1px solid #ddd; }
        .table tr:nth-child(even) { background-color: #f8f9fa; }
        .summary { background-color: #ecf0f1; padding: 15px; margin: 20px 0; border-radius: 5px; }
        .footer { margin-top: 30px; padding-top: 10px; border-top: 1px solid #bdc3c7; text-align: center; color: #7f8c8d; font-size: 12px; }
        .badge { padding: 4px 8px; border-radius: 3px; font-size: 12px; }
        .badge-success { background-color: #27ae60; color: white; }
        .badge-warning { background-color: #f39c12; color: white; }
        .badge-danger { background-color: #e74c3c; color: white; }
        .badge-secondary { background-color: #95a5a6; color: white; }
    </style>
</head>
<body>
    <div class="header">
        <h1><?php echo $titulo; ?></h1>
        <div class="subtitle">
            PRESTLAB - Sistema de Gestión de Préstamos<br>
            Generado el: <?php echo $fecha_generacion; ?>
        </div>
    </div>

    <?php if ($tipo === 'inventario'): ?>
        <!-- Reporte de Inventario -->
        <div class="summary">
            <strong>Resumen:</strong> 
            <?php echo count($reporte); ?> equipos encontrados |
            Total unidades: <?php echo array_sum(array_column($reporte, 'cantidad_total')); ?> |
            Unidades disponibles: <?php echo array_sum(array_column($reporte, 'cantidad_disponible')); ?>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Equipo</th>
                    <th>Descripción</th>
                    <th>Estado</th>
                    <th>Total</th>
                    <th>Disponible</th>
                    <th>Prestados</th>
                    <th>% Disp.</th>
                    <th>Fecha Adq.</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reporte as $equipo): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($equipo['nombre_equipo']); ?></strong></td>
                        <td><?php echo htmlspecialchars(substr($equipo['descripcion'] ?? 'Sin descripción', 0, 50)); ?>...</td>
                        <td>
                            <span class="badge 
                                <?php 
                                switch($equipo['id_estado_equipo']) {
                                    case 1: echo 'badge-success'; break;
                                    case 2: echo 'badge-warning'; break;
                                    case 3: echo 'badge-danger'; break;
                                    case 4: echo 'badge-secondary'; break;
                                    default: echo 'badge-secondary';
                                }
                                ?>">
                                <?php echo htmlspecialchars($equipo['nombre_estado']); ?>
                            </span>
                        </td>
                        <td><?php echo $equipo['cantidad_total']; ?></td>
                        <td><?php echo $equipo['cantidad_disponible']; ?></td>
                        <td><?php echo $equipo['cantidad_prestada']; ?></td>
                        <td><?php echo $equipo['porcentaje_disponible']; ?>%</td>
                        <td><?php echo date('d/m/Y', strtotime($equipo['fecha_adquisicion'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    <?php elseif ($tipo === 'prestamos'): ?>
        <!-- Reporte de Préstamos -->
        <div class="summary">
            <strong>Resumen:</strong> 
            <?php echo count($reporte); ?> préstamos encontrados |
            Total equipos prestados: <?php echo array_sum(array_column($reporte, 'total_unidades')); ?> |
            Préstamos activos: <?php echo count(array_filter($reporte, fn($p) => $p['estado_actual'] === 'En Curso')); ?>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Usuario</th>
                    <th>Estado</th>
                    <th>Fecha Préstamo</th>
                    <th>Fecha Límite</th>
                    <th>Días Préstamo</th>
                    <th>Equipos</th>
                    <th>Unidades</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reporte as $prestamo): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($prestamo['codigo_prestamo']); ?></strong></td>
                        <td><?php echo htmlspecialchars($prestamo['nombre'] . ' ' . $prestamo['apellido']); ?></td>
                        <td>
                            <span class="badge 
                                <?php 
                                switch($prestamo['estado_actual']) {
                                    case 'Vencido': echo 'badge-danger'; break;
                                    case 'En Curso': echo 'badge-success'; break;
                                    case 'Completado': echo 'badge-secondary'; break;
                                    default: echo 'badge-warning';
                                }
                                ?>">
                                <?php echo $prestamo['estado_actual']; ?>
                            </span>
                        </td>
                        <td><?php echo date('d/m/Y', strtotime($prestamo['fecha_prestamo'])); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($prestamo['fecha_limite_devolucion'])); ?></td>
                        <td><?php echo $prestamo['dias_prestamo']; ?> días</td>
                        <td><?php echo $prestamo['total_equipos']; ?></td>
                        <td><?php echo $prestamo['total_unidades']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    <?php elseif ($tipo === 'usuarios'): ?>
        <!-- Reporte de Usuarios -->
        <div class="summary">
            <strong>Resumen:</strong> 
            <?php echo count($reporte); ?> usuarios encontrados |
            Total préstamos: <?php echo array_sum(array_column($reporte, 'total_prestamos')); ?> |
            Préstamos activos: <?php echo array_sum(array_column($reporte, 'prestamos_activos')); ?>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Correo</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Fecha Registro</th>
                    <th>Total Préstamos</th>
                    <th>Activos</th>
                    <th>Vencidos</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reporte as $usuario): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?></strong></td>
                        <td><?php echo htmlspecialchars($usuario['correo']); ?></td>
                        <td>
                            <span class="badge <?php echo $usuario['id_rol'] == 1 ? 'badge-danger' : 'badge-success'; ?>">
                                <?php echo htmlspecialchars($usuario['nombre_rol']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?php echo $usuario['estado'] == 'activo' ? 'badge-success' : 'badge-secondary'; ?>">
                                <?php echo ucfirst($usuario['estado']); ?>
                            </span>
                        </td>
                        <td><?php echo date('d/m/Y', strtotime($usuario['fecha_creacion'])); ?></td>
                        <td><?php echo $usuario['total_prestamos']; ?></td>
                        <td><?php echo $usuario['prestamos_activos']; ?></td>
                        <td><?php echo $usuario['prestamos_vencidos']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <div class="footer">
        PRESTLAB - Sistema de Gestión de Préstamos de Laboratorio<br>
        Reporte generado automáticamente - <?php echo date('d/m/Y H:i:s'); ?>
    </div>

    <script>
        // Auto-impresión para simular PDF
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>