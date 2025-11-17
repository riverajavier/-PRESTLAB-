<?php
// detalle_prestamo.php - Vista para usuarios normales
require_once __DIR__ . '/../../core/Session.php';
Session::checkAuth();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle del Préstamo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .navbar-custom { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .card-header { background-color: #fff; border-bottom: 2px solid #dee2e6; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
    <div class="container">
        <a class="navbar-brand" href="#"><i class="bi bi-box-seam"></i> PRESTLAB</a>
        <div class="d-flex align-items-center">
            <span class="navbar-text me-3"><i class="bi bi-person-circle"></i> <?= htmlspecialchars(Session::get('user_nombre')) ?></span>
            <a href="/prestlab/public/index.php?action=logout" class="btn btn-outline-light btn-sm"><i class="bi bi-box-arrow-right"></i> Salir</a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <h2>Detalle del Préstamo</h2>

    <?php if (empty($prestamo) || empty($detalles)): ?>
        <div class="alert alert-warning">No se encontró información del préstamo.</div>
    <?php else: ?>

        <!-- Información general del préstamo -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-clipboard-check"></i> Información General</h5>
            </div>
            <div class="card-body">
                <p><strong>Código:</strong> <?= htmlspecialchars($prestamo['codigo_prestamo']) ?></p>
                <p><strong>Fecha de solicitud:</strong> <?= date('d/m/Y H:i', strtotime($prestamo['fecha_prestamo'])) ?></p>
                <p><strong>Fecha límite:</strong> <?= date('d/m/Y', strtotime($prestamo['fecha_limite_devolucion'])) ?></p>
                <p><strong>Estado:</strong>
                    <span class="badge bg-<?= $prestamo['id_estado_prestamo'] == 1 ? 'success' : ($prestamo['id_estado_prestamo'] == 2 ? 'warning' : 'secondary') ?>">
                        <?= htmlspecialchars($prestamo['estado_prestamo']) ?>
                    </span>
                </p>
            </div>
        </div>

        <!-- Equipos incluidos -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-box"></i> Equipos Incluidos</h5>
            </div>
            <div class="card-body">
                <ul class="list-group">
                    <?php foreach ($detalles as $item): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?= htmlspecialchars($item['nombre_equipo']) ?></strong>
                                <br><small class="text-muted"><?= htmlspecialchars($item['descripcion'] ?? 'Sin descripción') ?></small>
                            </div>
                            <span class="badge bg-primary rounded-pill">Cantidad: <?= $item['cantidad'] ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <div class="mt-4 text-center">
            <a href="/prestlab/public/index.php?controller=prestamo&action=misPrestamos" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left"></i> Volver a Mis Préstamos
            </a>
        </div>

    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>