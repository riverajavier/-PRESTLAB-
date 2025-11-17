<?php
// registro.php - Vista de registro con estilo acorde al login
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PRESTLAB - Registrarse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <style>
        /* Mismo estilo que login */
        body {
            margin: 0;
            padding: 0;
            height: 100vh;
            background: url('/prestlab/public/assets/images/fondos/login-bg.jpg') no-repeat center center/cover;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .auth-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 40px;
            max-width: 450px;
            width: 100%;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .auth-icon {
            font-size: 3.5rem;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .auth-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .auth-subtitle {
            color: #6c757d;
            font-size: 0.95rem;
            margin-bottom: 25px;
        }

        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
            border: 1px solid #ced4da;
        }

        .form-control:focus {
            box-shadow: 0 0 0 0.2rem rgba(44, 62, 80, 0.25);
            border-color: #2c3e50;
        }

        .btn-auth {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            color: white;
            transition: 0.3s;
        }

        .btn-auth:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .link-custom {
            color: #2c3e50;
            text-decoration: none;
            font-weight: 500;
        }

        .link-custom:hover {
            text-decoration: underline;
        }

        .alert-custom {
            border-radius: 10px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

<div class="auth-card">
    <div class="text-center">
        <i class="bi bi-person-plus auth-icon"></i>
        <h3 class="auth-title">Crear Cuenta</h3>
        <p class="auth-subtitle">Únete a PRESTLAB</p>
    </div>

    <?php if (!empty($mensaje)): ?>
        <?php if ($tipo_mensaje == 'success'): ?>
            <!-- Mensaje de éxito mejorado con redirección automática -->
            <div class="alert alert-success alert-custom text-center" role="alert">
                <i class="bi bi-check-circle"></i>
                <?= htmlspecialchars($mensaje) ?>
            </div>
            <div class="text-center mt-4">
                <a href="/prestlab/public/index.php" class="btn btn-auth btn-lg w-100">
                    <i class="bi bi-box-arrow-in-right"></i> Ir al Inicio de Sesión
                </a>
            </div>

            <!-- Redirección automática tras 3 segundos -->
            <script>
                setTimeout(function () {
                    window.location.href = "/prestlab/public/index.php";
                }, 3000);
            </script>
        <?php else: ?>
            <!-- Mensaje de error (mantener formato actual) -->
            <div class="alert alert-<?= htmlspecialchars($tipo_mensaje) ?> alert-custom" role="alert">
                <i class="bi bi-exclamation-triangle"></i>
                <?= htmlspecialchars($mensaje) ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (empty($mensaje) || $tipo_mensaje != 'success'): ?>
        <form method="POST" action="">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="nombre" class="form-label">Nombre</label>
                    <input type="text" class="form-control" id="nombre" name="nombre" value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>" placeholder="Juan" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="apellido" class="form-label">Apellido</label>
                    <input type="text" class="form-control" id="apellido" name="apellido" value="<?= htmlspecialchars($_POST['apellido'] ?? '') ?>" placeholder="Pérez" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="correo" class="form-label">Correo electrónico</label>
                <input type="email" class="form-control" id="correo" name="correo" value="<?= htmlspecialchars($_POST['correo'] ?? '') ?>" placeholder="correo@ejemplo.com" required>
            </div>

            <div class="mb-3">
                <label for="contrasena" class="form-label">Contraseña</label>
                <input type="password" class="form-control" id="contrasena" name="contrasena" placeholder="Mínimo 8 caracteres" required>
                <div class="form-text">Debe contener al menos 8 caracteres</div>
            </div>

            <div class="mb-3">
                <label for="confirmar_contrasena" class="form-label">Confirmar Contraseña</label>
                <input type="password" class="form-control" id="confirmar_contrasena" name="confirmar_contrasena" placeholder="Repite tu contraseña" required>
            </div>

            <button type="submit" class="btn btn-auth w-100 mb-3">
                <i class="bi bi-person-plus"></i> Crear Cuenta
            </button>
        </form>

        <div class="text-center">
            <p class="mb-0">¿Ya tienes cuenta? <a href="/prestlab/public/index.php" class="link-custom">Inicia sesión</a></p>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>