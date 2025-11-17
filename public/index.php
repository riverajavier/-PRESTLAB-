<?php
// index.php - Front Controller 🚀
// Punto de entrada principal de la aplicación

// --- Inclusión de Archivos Necesarios ---

// 1. Configuración y Conexión a la Base de Datos
require_once '../config/database.php';

// 2. Modelos
require_once '../app/models/UsuarioModel.php';
require_once '../app/models/EquipoModel.php';
require_once '../app/models/PrestamoModel.php';
require_once '../app/models/ReporteModel.php'; // ✅ agregado

// 3. Controladores
require_once '../app/controllers/AuthController.php';
require_once '../app/controllers/InventarioController.php';
require_once '../app/controllers/PrestamoController.php';
require_once '../app/controllers/DevolucionController.php';
require_once '../app/controllers/UsuarioController.php';
require_once '../app/controllers/ReporteController.php'; // ✅ agregado


// --- Inicialización de la Base de Datos ---
$database = new Database();
$db = $database->getConnection();


// --- Enrutamiento (Routing) ---
$controller = $_GET['controller'] ?? 'auth';
$action = $_GET['action'] ?? 'login';


// --- Controladores y Acciones ---

// 1. Controlador: Autenticación
if ($controller == 'auth') {
    $appController = new AuthController($db);

    if ($action == 'login') {
        $appController->login();
    } elseif ($action == 'registro') {
        $appController->registro();
    } elseif ($action == 'logout') {
        $appController->logout();
    } else {
        $appController->login();
    }

// 2. Controlador: Inventario
} elseif ($controller == 'inventario') {
    $appController = new InventarioController($db);

    if ($action == 'consultar') {
        $appController->consultarInventario();
    } elseif ($action == 'verificarDisponibilidad') {
        $appController->verificarDisponibilidad();
    } elseif ($action == 'gestionarInventario') {
        $appController->gestionarInventario();
    } elseif ($action == 'crearEquipo') {
        $appController->crearEquipo();
    } elseif ($action == 'actualizarEquipo') {
        $appController->actualizarEquipo();
    } elseif ($action == 'editarEquipo') {
        $appController->editarEquipo(); // ✅ AGREGADO
    } elseif ($action == 'eliminarEquipo') {
        $appController->eliminarEquipo();
    } elseif ($action == 'obtenerEquipo') {
        $appController->obtenerEquipo();
    } elseif ($action == 'obtenerEquipos') {
        $appController->obtenerEquipos();
    } else {
        $appController->consultarInventario();
    }

// 3. Controlador: Préstamos
} elseif ($controller == 'prestamo') {
    $appController = new PrestamoController($db);

    if ($action == 'solicitar') {
        $appController->solicitarPrestamo();
    } elseif ($action == 'misPrestamos') {
        $appController->misPrestamos();
    } elseif ($action == 'verDetalle') {
        $appController->verDetallePrestamo();
    } elseif ($action == 'gestionarPrestamos') {
        $appController->gestionarPrestamos();
    } elseif ($action == 'crearPrestamoPresencial') {
        $appController->crearPrestamoPresencial();
    } elseif ($action == 'obtenerDetallePrestamo') {
        $appController->obtenerDetallePrestamo();
    } elseif ($action == 'actualizarPrestamo') {
        $appController->actualizarPrestamo();
    } else {
        $appController->misPrestamos();
    }

// 4. Controlador: Devoluciones
} elseif ($controller == 'devolucion') {
    $appController = new DevolucionController($db);

    if ($action == 'gestionarDevoluciones') {
        $appController->gestionarDevoluciones();
    } elseif ($action == 'procesarDevolucion') {
        $appController->procesarDevolucion();
    } elseif ($action == 'solicitarDevolucion') {
        $appController->solicitarDevolucion();
    } else {
        $appController->gestionarDevoluciones();
    }

// 5. Controlador: Usuarios
} elseif ($controller == 'usuario') {
    $appController = new UsuarioController($db);

    if ($action == 'gestionarUsuarios') {
        $appController->gestionarUsuarios();
    } elseif ($action == 'crearUsuario') {
        $appController->crearUsuario();
    } elseif ($action == 'actualizarUsuario') {
        $appController->actualizarUsuario();
    } elseif ($action == 'eliminarUsuario') {
        $appController->eliminarUsuario();
    } elseif ($action == 'obtenerUsuario') {
        $appController->obtenerUsuario();
    } elseif ($action == 'obtenerUsuarios') {
        $appController->obtenerUsuarios();
    } elseif ($action == 'toggleEstadoUsuario') {
        $appController->toggleEstadoUsuario(); // ✅ AGREGADO
    } else {
        $appController->gestionarUsuarios();
    }

// 6. Controlador: Reportes ✅ NUEVO
} elseif ($controller == 'reporte') {
    $appController = new ReporteController($db);

    if ($action == 'gestionarReportes') {
        $appController->gestionarReportes();
    } elseif ($action == 'generarReporteInventario') {
        $appController->generarReporteInventario();
    } elseif ($action == 'generarReportePrestamos') {
        $appController->generarReportePrestamos();
    } elseif ($action == 'generarReporteUsuarios') {
        $appController->generarReporteUsuarios();
    } elseif ($action == 'exportarPDF') {
        $appController->exportarPDF();
    } elseif ($action == 'exportarExcel') {
        $appController->exportarExcel();
    } else {
        $appController->gestionarReportes();
    }

// 7. Controlador por Defecto
} else {
    $appController = new AuthController($db);
    $appController->login();
}
?>