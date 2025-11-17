<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PRESTLAB - Mis Préstamos</title>
    <!-- Incluye Bootstrap 5 CSS para estilos y componentes base -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Incluye Bootstrap Icons para la iconografía -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- Estilos CSS Personalizados -->
    <style>
        /* Estilo general del cuerpo */
        body {
            background-color: #f8f9fa; /* Fondo gris claro de Bootstrap */
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        /* Estilo personalizado para la barra de navegación con gradiente */
        .navbar-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        /* Clase base para las etiquetas de estado */
        .badge-estado {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
        }
        /* Estados de préstamo definidos por colores */
        .badge-activo { background-color: #28a745; color: white; } /* Verde */
        .badge-vencido { background-color: #dc3545; color: white; } /* Rojo */
        .badge-devuelto { background-color: #6c757d; color: white; } /* Gris */

        /* Estilo para la tarjeta de cada préstamo individual */
        .prestamo-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid #667eea; /* Borde izquierdo destacado */
        }
    </style>
</head>
<body>
    <!-- Navbar principal -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
        <div class="container">
            <!-- Logo de la aplicación -->
            <a class="navbar-brand" href="#">
                <i class="bi bi-box-seam"></i> PRESTLAB
            </a>
            <div class="d-flex align-items-center">
                <!-- Muestra el nombre del usuario logeado (PHP) -->
                <span class="navbar-text me-3">
                    <i class="bi bi-person-circle"></i> <?php echo Session::get('user_nombre'); ?>
                </span>
                <!-- Grupo de botones de navegación -->
                <div class="btn-group">
                    <!-- Enlace al inventario -->
                    <a href="/prestlab/public/index.php?controller=inventario&action=consultar" 
                       class="btn btn-outline-light btn-sm">
                        <i class="bi bi-search"></i> Inventario
                    </a>
                    <!-- Enlace dinámico al Dashboard (Admin o Usuario) basado en el rol (PHP) -->
                    <a href="<?php echo Session::get('user_rol') == 1 ? '/prestlab/public/admin/dashboard.php' : '/prestlab/public/usuario/dashboard.php'; ?>" 
                       class="btn btn-outline-light btn-sm">
                        <i class="bi bi-house"></i> Dashboard
                    </a>
                    <!-- Enlace para cerrar sesión -->
                    <a href="/prestlab/public/index.php?action=logout" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-box-arrow-right"></i> Salir
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Contenedor principal de la vista -->
    <div class="container mt-4">
        <!-- Encabezado y botones de filtrado -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2>Mis Préstamos</h2>
                <p class="text-muted">Gestiona y revisa el historial de tus préstamos</p>
            </div>
            <!-- Botones para alternar entre "Todos" y "Activos" -->
            <div class="btn-group">
                <!-- Botón "Todos los Préstamos" (activo si $solo_activos es falso) -->
                <a href="/prestlab/public/index.php?controller=prestamo&action=misPrestamos" 
                   class="btn btn-outline-primary <?php echo !$solo_activos ? 'active' : ''; ?>">
                    Todos los Préstamos
                </a>
                <!-- Botón "Préstamos Activos" (activo si $solo_activos es verdadero) -->
                <a href="/prestlab/public/index.php?controller=prestamo&action=misPrestamos&activos=1" 
                   class="btn btn-outline-primary <?php echo $solo_activos ? 'active' : ''; ?>">
                    Préstamos Activos
                </a>
            </div>
        </div>

        <!-- Lógica condicional (PHP) para mostrar la lista o el mensaje de vacío -->
        <?php if (empty($prestamos)): ?>
            <!-- Mensaje de estado vacío: si no hay préstamos registrados -->
            <div class="alert alert-info text-center">
                <i class="bi bi-info-circle" style="font-size: 2rem;"></i>
                <h4 class="mt-3">No tienes préstamos registrados</h4>
                <p class="mb-0">Cuando solicites equipos en préstamo, aparecerán aquí.</p>
                <!-- Botón para ir al inventario y solicitar equipos -->
                <a href="/prestlab/public/index.php?controller=inventario&action=consultar" class="btn btn-primary mt-3">
                    <i class="bi bi-search"></i> Explorar Inventario
                </a>
            </div>
        <?php else: ?>
            <!-- Contenedor de la lista de préstamos -->
            <div class="row">
                <!-- Bucle (PHP) para iterar sobre cada préstamo en el array $prestamos -->
                <?php foreach ($prestamos as $prestamo): ?>
                    <!-- Cada préstamo ocupa 6 columnas (mitad de la fila) en md y superior -->
                    <div class="col-md-6">
                        <div class="prestamo-card">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <!-- Código del préstamo -->
                                    <h5 class="mb-1"><?php echo htmlspecialchars($prestamo['codigo_prestamo']); ?></h5>
                                    <!-- Fecha de solicitud -->
                                    <p class="text-muted mb-0 small">
                                        <i class="bi bi-calendar"></i> 
                                        Solicitado: <?php echo date('d/m/Y H:i', strtotime($prestamo['fecha_prestamo'])); ?>
                                    </p>
                                </div>
                                <div>
                                    <!-- Lógica (PHP) para determinar la clase CSS del estado del préstamo -->
                                    <?php
                                    $badge_class = '';
                                    switch($prestamo['id_estado_prestamo']) {
                                        case 1: 
                                            $badge_class = 'badge-activo'; // Activo (Verde)
                                            break;
                                        case 2: 
                                            $badge_class = 'badge-vencido'; // Vencido (Rojo)
                                            break;
                                        case 3: 
                                            $badge_class = 'badge-devuelto'; // Devuelto (Gris)
                                            break;
                                        default: 
                                            $badge_class = 'badge-secondary'; // Cualquier otro estado
                                    }
                                    ?>
                                    <!-- Muestra el estado con el estilo de badge correspondiente -->
                                    <span class="badge-estado <?php echo $badge_class; ?>">
                                        <?php echo htmlspecialchars($prestamo['estado_prestamo']); ?>
                                    </span>
                                </div>
                            </div>

                            <div class="mb-3">
                                <small class="text-muted">Equipos:</small>
                                <!-- Nombres de los equipos prestados -->
                                <p class="mb-1"><?php echo htmlspecialchars($prestamo['nombres_equipos']); ?></p>
                                <!-- Conteo total de equipos -->
                                <small class="text-muted">
                                    Total equipos: <?php echo $prestamo['total_equipos']; ?>
                                </small>
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <!-- Fecha límite de devolución -->
                                <small class="text-muted">
                                    <i class="bi bi-clock"></i> 
                                    Límite: <?php echo date('d/m/Y', strtotime($prestamo['fecha_limite_devolucion'])); ?>
                                </small>
                                <div>
                                    <!-- Botón para ver el detalle del préstamo -->
                                    <a href="/prestlab/public/index.php?controller=prestamo&action=verDetalle&id=<?php echo $prestamo['id_prestamo']; ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> Ver Detalles
                                    </a>
                                </div>
                            </div>

                            <!-- Lógica condicional (PHP) para mostrar advertencia si está VENCIDO -->
                            <?php if ($prestamo['id_estado_prestamo'] == 1 && strtotime($prestamo['fecha_limite_devolucion']) < strtotime('today')): ?>
                                <div class="mt-2">
                                    <small class="text-danger">
                                        <i class="bi bi-exclamation-triangle"></i> 
                                        Este préstamo está vencido
                                    </small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Incluye Bootstrap 5 JavaScript con Pooper (necesario para componentes dinámicos) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
