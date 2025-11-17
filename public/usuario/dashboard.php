<?php
// dashboard.php (o usuario/dashboard.php) - La Vista Principal del Usuario

// --- 1. Lógica de Seguridad y Sesión ---

// Incluyo mi clase personalizada para manejar sesiones de forma segura (Session.php).
require_once '../../app/core/Session.php';

// *Verificación de Autenticación Crítica*
// Si el valor 'user_id' no existe en la sesión (el usuario no ha iniciado sesión):
if (!Session::get('user_id')) {
    // Redirijo inmediatamente al usuario al punto de entrada principal (login).
    header("Location: /prestlab/public/index.php");
    // Detengo la ejecución del script para asegurar que nada del HTML se muestre.
    exit();
}

// *Carga de Datos de Sesión*
// Obtengo los datos del usuario logueado usando mi clase Session, en lugar de acceder directamente a $_SESSION.
$user_nombre = Session::get('user_nombre'); // Nombre completo del usuario.
$user_rol = Session::get('user_rol');       // Rol del usuario (ej. 'alumno', 'profesor', etc.).
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PRESTLAB - Panel de Usuario</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr' /'net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        body {
            background-color: #f8f9fa; /* Fondo gris claro */
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar-custom {
            /* Navbar con un gradiente de color atractivo */
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .stat-card {
            /* Estilo para las tarjetas de estadísticas */
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid #667eea; /* Línea de color para destacar */
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="bi bi-box-seam"></i> PRESTLAB
            </a>
            
            <div class="d-flex align-items-center">
                <span class="navbar-text me-3">
                    <i class="bi bi-person-circle"></i> 
                    <?php echo $_SESSION['user_nombre']; ?> 
                </span>
                <a href="/prestlab/public/index.php?action=logout" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-box-arrow-right"></i> Salir
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>Bienvenido, <?php echo explode(' ', $_SESSION['user_nombre'])[0]; ?></h2>
        <p class="text-muted">Panel de Usuario - Gestión de Préstamos</p>

        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <h3>0</h3> <p><i class="bi bi-clipboard-check"></i> Préstamos Activos</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <h3>0</h3> <p><i class="bi bi-clock-history"></i> Préstamos Totales</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <h3>0</h3> <p><i class="bi bi-exclamation-triangle"></i> Por Vencer</p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-search" style="font-size: 3rem; color: #667eea;"></i>
                        <h5 class="mt-3">Consultar Inventario</h5>
                        <p class="text-muted">Ver equipos disponibles para préstamo</p>
                        <a href="/prestlab/public/index.php?controller=inventario&action=consultar" class="btn btn-primary">
                            Ir al Inventario
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-3">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-clock-history" style="font-size: 3rem; color: #764ba2;"></i>
                        <h5 class="mt-3">Mis Préstamos</h5>
                        <p class="text-muted">Ver todos tus préstamos activos e histórico</p>
                        <a href="/prestlab/public/index.php?controller=prestamo&action=misPrestamos" class="btn btn-outline-primary">
                            Ver Mis Préstamos
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>