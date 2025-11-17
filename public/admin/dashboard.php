<?php
// dashboard.php (admin) - La Vista Principal para Administradores
//
// Este es el panel principal del administrador del sistema PRESTLAB.
// Desde aquí el administrador puede acceder a las secciones de inventario, préstamos, devoluciones y más.
//
// --- 1. Lógica de Seguridad y Sesión ---

require_once '../../app/core/Session.php'; // Gestión de sesión segura.

// *Verificación de Autenticación Crítica*
if (!Session::get('user_id')) {
    header("Location: /prestlab/public/index.php");
    exit();
}

// *Carga de Datos de Sesión*
$user_nombre = Session::get('user_nombre');
$user_rol = Session::get('user_rol');

// --- Cargar datos reales para el dashboard ---
require_once '../../config/database.php';
require_once '../../app/models/EquipoModel.php';
require_once '../../app/models/PrestamoModel.php';
require_once '../../app/models/UsuarioModel.php';

$database = new Database();
$db = $database->getConnection();

$equipoModel = new EquipoModel($db);
$prestamoModel = new PrestamoModel($db);
$usuarioModel = new UsuarioModel($db);

// Datos reales
$totalEquipos = $equipoModel->contarEquipos();
$prestamosActivos = $prestamoModel->obtenerEstadisticasPrestamos()['prestamos_activos'] ?? 0;
$prestamosVencidos = $prestamoModel->obtenerEstadisticasPrestamos()['prestamos_vencidos'] ?? 0;
$usuariosActivos = $usuarioModel->obtenerEstadisticasUsuarios()['usuarios_activos'] ?? 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PRESTLAB - Panel de Administración</title>
    
    <!-- Bootstrap y Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- Estilos personalizados -->
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
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
    </style>
</head>
<body>
    <!-- --- 2. Barra de Navegación --- -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="bi bi-person-badge"></i> PRESTLAB - Admin
            </a>
            
            <div class="d-flex align-items-center">
                <span class="navbar-text me-3">
                    <i class="bi bi-person-circle"></i> 
                    <?php echo htmlspecialchars($user_nombre); ?>
                </span>
                <a href="/prestlab/public/index.php?action=logout" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-box-arrow-right"></i> Salir
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- --- 3. Sidebar (Menú de Navegación Lateral) --- -->
            <div class="col-md-3 col-lg-2 sidebar">
                <nav class="nav flex-column">
                    <a class="nav-link active" href="/prestlab/public/admin/dashboard.php">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                    <a class="nav-link" href="/prestlab/public/index.php?controller=inventario&action=gestionarInventario">
                        <i class="bi bi-box-seam"></i> Inventario
                    </a>
                    <a class="nav-link" href="/prestlab/public/index.php?controller=prestamo&action=gestionarPrestamos">
                        <i class="bi bi-clipboard-check"></i> Préstamos
                    </a>
                    <a class="nav-link" href="/prestlab/public/index.php?controller=devolucion&action=gestionarDevoluciones">
                        <i class="bi bi-arrow-return-left"></i> Devoluciones
                    </a>
                    <a class="nav-link" href="/prestlab/public/index.php?controller=usuario&action=gestionarUsuarios">
                        <i class="bi bi-people"></i> Usuarios
                    </a>
                    <!-- 🔹 Nuevo enlace agregado -->
                    <a class="nav-link" href="/prestlab/public/index.php?controller=reporte&action=gestionarReportes">
                        <i class="bi bi-graph-up"></i> Reportes
                    </a>
                </nav>
            </div>

            <!-- --- 4. Contenido Principal (Dashboard) --- -->
            <div class="col-md-9 col-lg-10 p-4">
                <h2>Dashboard de Administración</h2>
                <p class="text-muted">Resumen general del sistema</p>

                <!-- Sección de Estadísticas -->
                <div class="row mb-4">
                    <!-- Total Equipos -->
                    <div class="col-md-3">
                        <div class="stat-card">
                            <i class="bi bi-box-seam" style="font-size: 2rem; color: #667eea;"></i>
                            <h3 class="mt-2 mb-0"><?= $totalEquipos ?></h3>
                            <p class="text-muted mb-0">Total Equipos</p>
                        </div>
                    </div>

                    <!-- Préstamos Activos -->
                    <div class="col-md-3">
                        <div class="stat-card">
                            <i class="bi bi-clipboard-check" style="font-size: 2rem; color: #28a745;"></i>
                            <h3 class="mt-2 mb-0"><?= $prestamosActivos ?></h3>
                            <p class="text-muted mb-0">Préstamos Activos</p>
                        </div>
                    </div>

                    <!-- Vencidos -->
                    <div class="col-md-3">
                        <div class="stat-card">
                            <i class="bi bi-exclamation-triangle" style="font-size: 2rem; color: #ffc107;"></i>
                            <h3 class="mt-2 mb-0"><?= $prestamosVencidos ?></h3>
                            <p class="text-muted mb-0">Vencidos</p>
                        </div>
                    </div>

                    <!-- Usuarios Activos -->
                    <div class="col-md-3">
                        <div class="stat-card">
                            <i class="bi bi-people" style="font-size: 2rem; color: #764ba2;"></i>
                            <h3 class="mt-2 mb-0"><?= $usuariosActivos ?></h3>
                            <p class="text-muted mb-0">Usuarios Activos</p>
                        </div>
                    </div>
                </div>

                <!-- Acciones rápidas -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Acciones Rápidas</h6>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="/prestlab/public/index.php?controller=prestamo&action=gestionarPrestamos" class="btn btn-outline-primary">
                                        <i class="bi bi-plus-circle"></i> Registrar Préstamo
                                    </a>
                                    <a href="/prestlab/public/index.php?controller=devolucion&action=gestionarDevoluciones" class="btn btn-outline-success">
                                        <i class="bi bi-arrow-return-left"></i> Procesar Devolución
                                    </a>
                                    <a href="/prestlab/public/index.php?controller=inventario&action=gestionarInventario" class="btn btn-outline-secondary">
                                        <i class="bi bi-box"></i> Agregar Equipo
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Información del sistema -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Sistema</h6>
                            </div>
                            <div class="card-body">
                                <p><strong>Versión:</strong> PRESTLAB v1.0</p>
                                <p><strong>Usuario:</strong> <?php echo htmlspecialchars($user_nombre); ?></p>
                                <p><strong>Rol:</strong> Administrador</p>
                                <p><strong>Último acceso:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>