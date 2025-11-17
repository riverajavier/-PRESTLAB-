<?php
// login.php - Vista de login con estilo profesional
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PRESTLAB - Iniciar Sesión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <style>
        /* Fondo con imagen */
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

        /* Tarjeta de login */
        .login-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 40px;
            max-width: 420px;
            width: 100%;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        /* Logo o ícono */
        .login-icon {
            font-size: 3.5rem;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        /* Título */
        .login-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .login-subtitle {
            color: #6c757d;
            font-size: 0.95rem;
            margin-bottom: 25px;
        }

        /* Inputs */
        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
            border: 1px solid #ced4da;
        }

        .form-control:focus {
            box-shadow: 0 0 0 0.2rem rgba(44, 62, 80, 0.25);
            border-color: #2c3e50;
        }

        /* Botón */
        .btn-login {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            color: white;
            transition: 0.3s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        /* Enlaces */
        .link-custom {
            color: #2c3e50;
            text-decoration: none;
            font-weight: 500;
        }

        .link-custom:hover {
            text-decoration: underline;
        }

        /* Alertas */
        .alert-custom {
            border-radius: 10px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="text-center">
        <i class="bi bi-box-seam login-icon"></i>
        <h3 class="login-title">Bienvenido a PRESTLAB</h3>
        <p class="login-subtitle">Sistema de Gestión de Préstamos de Laboratorio</p>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-custom" role="alert">
            <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="mb-3">
            <label for="correo" class="form-label">Correo electrónico</label>
            <input type="email" class="form-control" id="correo" name="correo" placeholder="usuario@ejemplo.com" required>
        </div>

        <div class="mb-3">
            <label for="contrasena" class="form-label">Contraseña</label>
            <input type="password" class="form-control" id="contrasena" name="contrasena" placeholder="••••••••" required>
        </div>

        <button type="submit" class="btn btn-login w-100 mb-3">
            <i class="bi bi-box-arrow-in-right"></i> Iniciar Sesión
        </button>
    </form>

    <div class="text-center">
        <p class="mb-0">¿No tienes cuenta? <a href="/prestlab/public/index.php?action=registro" class="link-custom">Regístrate aquí</a></p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>