<?php
// Cabeceras para Excel
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=reporte_<?php echo $titulo; ?>_<?php echo date('Y-m-d'); ?>.xls");
header("Pragma: no-cache");
header("Expires: 0");
?>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        table { border-collapse: collapse; width: 100%; }
        th { background-color: #2c3e50; color: white; font-weight: bold; padding: 8px; border: 1px solid #34495e; }
        td { padding: 6px; border: 1px solid #bdc3c7; }
        .title { font-size: 18px; font-weight: bold; color: #2c3e50; margin-bottom: 10px; }
        .subtitle { color: #7f8c8d; margin-bottom: 20px; }
        .summary { background-color: #ecf0f1; padding: 10px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="title">Reporte de <?php echo $titulo; ?> - PRESTLAB</div>
    <div class="subtitle">Generado el: <?php echo date('d/m/Y H:i:s'); ?></div>

    <?php if ($tipo === 'inventario'): ?>
        <!-- Excel Inventario -->
        <div class="summary">
            <strong>Resumen:</strong> 
            <?php echo count($reporte); ?> equipos | 
            Total unidades: <?php echo array_sum(array_column($reporte, 'cantidad_total')); ?> | 
            Disponibles: <?php echo array_sum(array_column($reporte, 'cantidad_disponible')); ?>
        </div>

        <table border="1">
            <thead>
                <tr>
                    <th>Equipo</th>
                    <th>Descripción</th>
                    <th>Estado</th>
                    <th>Total</th>
                    <th>Disponible</th>
                    <th>Prestados</th>
                    <th>% Disponible</th>
                    <th>Fecha Adquisición</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reporte as $equipo): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($equipo['nombre_equipo']); ?></td>
                        <td><?php echo htmlspecialchars($equipo['descripcion'] ?? 'Sin descripción'); ?></td>
                        <td><?php echo htmlspecialchars($equipo['nombre_estado']); ?></td>
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
        <!-- Excel Préstamos -->
        <div class="summary">
            <strong>Resumen:</strong> 
            <?php echo count($reporte); ?> préstamos | 
            Total equipos: <?php echo array_sum(array_column($reporte, 'total_unidades')); ?> | 
            Activos: <?php echo count(array_filter($reporte, fn($p) => $p['estado_actual'] === 'En Curso')); ?>
        </div>

        <table border="1">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Usuario</th>
                    <th>Correo</th>
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
                        <td><?php echo htmlspecialchars($prestamo['codigo_prestamo']); ?></td>
                        <td><?php echo htmlspecialchars($prestamo['nombre'] . ' ' . $prestamo['apellido']); ?></td>
                        <td><?php echo htmlspecialchars($prestamo['correo']); ?></td>
                        <td><?php echo $prestamo['estado_actual']; ?></td>
                        <td><?php echo date('d/m/Y', strtotime($prestamo['fecha_prestamo'])); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($prestamo['fecha_limite_devolucion'])); ?></td>
                        <td><?php echo $prestamo['dias_prestamo']; ?></td>
                        <td><?php echo $prestamo['total_equipos']; ?></td>
                        <td><?php echo $prestamo['total_unidades']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    <?php elseif ($tipo === 'usuarios'): ?>
        <!-- Excel Usuarios -->
        <div class="summary">
            <strong>Resumen:</strong> 
            <?php echo count($reporte); ?> usuarios | 
            Total préstamos: <?php echo array_sum(array_column($reporte, 'total_prestamos')); ?> | 
            Activos: <?php echo array_sum(array_column($reporte, 'prestamos_activos')); ?>
        </div>

        <table border="1">
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
                        <td><?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?></td>
                        <td><?php echo htmlspecialchars($usuario['correo']); ?></td>
                        <td><?php echo htmlspecialchars($usuario['nombre_rol']); ?></td>
                        <td><?php echo ucfirst($usuario['estado']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($usuario['fecha_creacion'])); ?></td>
                        <td><?php echo $usuario['total_prestamos']; ?></td>
                        <td><?php echo $usuario['prestamos_activos']; ?></td>
                        <td><?php echo $usuario['prestamos_vencidos']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>