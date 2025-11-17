<?php
// Carga la clase de Sesión para manejar la autenticación
require_once '../app/core/Session.php';

/**
 * Clase controladora para manejar la generación de reportes y estadísticas.
 * Todas las funciones requieren el rol de Administrador (Rol 1).
 */
class ReporteController {
    private $reporteModel;
    private $usuarioModel; // Se mantienen los modelos, aunque no se usan directamente en este extracto
    private $equipoModel;
    private $prestamoModel;

    /**
     * Constructor del controlador.
     * @param object $db La conexión a la base de datos.
     */
    public function __construct($db) {
        // Inicializa los modelos necesarios
        $this->reporteModel = new ReporteModel($db);
        $this->usuarioModel = new UsuarioModel($db);
        $this->equipoModel = new EquipoModel($db);
        $this->prestamoModel = new PrestamoModel($db);
    }

    // --- Funciones de Administración y Visualización de Reportes ---

    /**
     * Muestra la vista principal del panel de reportes, incluyendo estadísticas clave.
     * Solo accesible para administradores.
     */
    public function gestionarReportes() {
        Session::checkAuth(); // 1. Verifica la autenticación
        
        // 2. Control de acceso: Si el rol NO es Administrador (Rol 1), redirige
        if (Session::getUserRole() != 1) {
            header("Location: /prestlab/public/index.php"); // Ruta de redirección verificada
            exit();
        }

        // 3. Obtiene datos clave para el dashboard/vista principal
        $estadisticas = $this->reporteModel->obtenerEstadisticasGenerales();
        $prestamos_proximos = $this->reporteModel->obtenerPrestamosProximosVencer();
        $equipos_baja_disponibilidad = $this->reporteModel->obtenerEquiposBajaDisponibilidad();

        // 4. Prepara y carga la vista de gestión
        $data = [
            'estadisticas' => $estadisticas,
            'prestamos_proximos' => $prestamos_proximos,
            'equipos_baja_disponibilidad' => $equipos_baja_disponibilidad
        ];

        extract($data);
        include_once '../app/views/admin/reportes/gestionar.php';
    }

    /**
     * Genera un reporte de inventario basado en filtros GET.
     * Devuelve la respuesta en formato JSON para ser consumido por AJAX.
     */
    public function generarReporteInventario() {
        Session::checkAuth();
        
        // 1. Control de acceso: Solo Admin
        if (Session::getUserRole() != 1) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            exit();
        }

        // 2. Procesa los filtros
        $filtros = [];
        if (!empty($_GET['estado'])) {
            $filtros['estado'] = (int)$_GET['estado'];
        }
        if (!empty($_GET['fecha_desde'])) {
            $filtros['fecha_desde'] = $_GET['fecha_desde'];
        }
        if (!empty($_GET['fecha_hasta'])) {
            $filtros['fecha_hasta'] = $_GET['fecha_hasta'];
        }

        // 3. Genera el reporte y devuelve JSON
        $reporte = $this->reporteModel->generarReporteInventario($filtros);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'reporte' => $reporte]);
        exit();
    }

    /**
     * Genera un reporte de préstamos basado en filtros GET.
     * Devuelve la respuesta en formato JSON para ser consumido por AJAX.
     */
    public function generarReportePrestamos() {
        Session::checkAuth();
        
        // 1. Control de acceso: Solo Admin
        if (Session::getUserRole() != 1) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            exit();
        }

        // 2. Procesa los filtros
        $filtros = [];
        if (!empty($_GET['fecha_desde'])) {
            $filtros['fecha_desde'] = $_GET['fecha_desde'];
        }
        if (!empty($_GET['fecha_hasta'])) {
            $filtros['fecha_hasta'] = $_GET['fecha_hasta'];
        }
        if (!empty($_GET['estado'])) {
            $filtros['estado'] = (int)$_GET['estado'];
        }

        // 3. Genera el reporte y devuelve JSON
        $reporte = $this->reporteModel->generarReportePrestamos($filtros);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'reporte' => $reporte]);
        exit();
    }

    /**
     * Genera un reporte de usuarios basado en filtros GET.
     * Devuelve la respuesta en formato JSON para ser consumido por AJAX.
     */
    public function generarReporteUsuarios() {
        Session::checkAuth();
        
        // 1. Control de acceso: Solo Admin
        if (Session::getUserRole() != 1) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            exit();
        }

        // 2. Procesa los filtros
        $filtros = [];
        if (!empty($_GET['rol'])) {
            $filtros['rol'] = (int)$_GET['rol'];
        }
        if (!empty($_GET['estado'])) {
            $filtros['estado'] = $_GET['estado']; // Asume que 'estado' puede ser un string (activo/inactivo, por ejemplo)
        }
        if (!empty($_GET['fecha_desde'])) {
            $filtros['fecha_desde'] = $_GET['fecha_desde'];
        }
        if (!empty($_GET['fecha_hasta'])) {
            $filtros['fecha_hasta'] = $_GET['fecha_hasta'];
        }

        // 3. Genera el reporte y devuelve JSON
        $reporte = $this->reporteModel->generarReporteUsuarios($filtros);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'reporte' => $reporte]);
        exit();
    }

    // --- Funciones de Exportación ---

    /**
     * Exporta un reporte al formato PDF.
     * Nota: Se asume que la vista 'exportar_pdf.php' contendrá la lógica de librería PDF (e.g., FPDF, Dompdf).
     */
    public function exportarPDF() {
        Session::checkAuth();
        
        // 1. Control de acceso: Solo Admin
        if (Session::getUserRole() != 1) {
            header("Location: /prestlab/public/index.php"); // Redirección de error verificada
            exit();
        }

        $tipo = $_GET['tipo'] ?? 'inventario'; // Define el tipo de reporte a generar
        $filtros = $_GET; // Los filtros se pasan directamente como GET

        // 2. Lógica para obtener los datos del reporte según el tipo
        switch ($tipo) {
            case 'inventario':
                $reporte = $this->reporteModel->generarReporteInventario($filtros);
                $titulo = "Reporte de Inventario";
                break;
            case 'prestamos':
                $reporte = $this->reporteModel->generarReportePrestamos($filtros);
                $titulo = "Reporte de Préstamos";
                break;
            case 'usuarios':
                $reporte = $this->reporteModel->generarReporteUsuarios($filtros);
                $titulo = "Reporte de Usuarios";
                break;
            default:
                $reporte = [];
                $titulo = "Reporte";
        }

        // 3. Prepara los datos y carga la vista de exportación a PDF
        $data = [
            'reporte' => $reporte,
            'titulo' => $titulo,
            'tipo' => $tipo,
            'fecha_generacion' => date('d/m/Y H:i:s')
        ];

        extract($data);
        // La vista 'exportar_pdf.php' debe enviar las cabeceras PDF y generar el contenido
        include_once '../app/views/admin/reportes/exportar_pdf.php';
        // No se requiere 'exit()' aquí si la vista maneja la salida completa del PDF.
    }

    /**
     * Exporta un reporte al formato Excel (CSV/XLS simple mediante cabeceras).
     */
    public function exportarExcel() {
        Session::checkAuth();
        
        // 1. Control de acceso: Solo Admin
        if (Session::getUserRole() != 1) {
            header("Location: /prestlab/public/index.php"); // Redirección de error verificada
            exit();
        }

        $tipo = $_GET['tipo'] ?? 'inventario'; // Define el tipo de reporte a generar
        $filtros = $_GET; // Los filtros se pasan directamente como GET

        // 2. Lógica para obtener los datos del reporte según el tipo
        switch ($tipo) {
            case 'inventario':
                $reporte = $this->reporteModel->generarReporteInventario($filtros);
                $titulo = "Inventario";
                break;
            case 'prestamos':
                $reporte = $this->reporteModel->generarReportePrestamos($filtros);
                $titulo = "Prestamos";
                break;
            case 'usuarios':
                $reporte = $this->reporteModel->generarReporteUsuarios($filtros);
                $titulo = "Usuarios";
                break;
            default:
                $reporte = [];
                $titulo = "Reporte";
        }

        // 3. Cabeceras para forzar la descarga del archivo Excel
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="reporte_' . $titulo . '_' . date('Y-m-d') . '.xls"');
        
        // 4. La vista 'exportar_excel.php' genera el contenido de la tabla CSV/XLS
        include_once '../app/views/admin/reportes/exportar_excel.php';
        exit();
    }
}
?>
