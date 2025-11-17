<?php
/**
 * Clase ReporteModel: Maneja la generación de reportes y obtención de estadísticas relacionadas con el sistema de gestión de equipos y préstamos.
 * 
 * Esta clase utiliza una conexión PDO ($conn) inyectada en el constructor para ejecutar consultas SQL preparadas.
 * Cada método prepara una consulta dinámica basada en filtros opcionales, bindea parámetros para seguridad (previniendo SQL injection),
 * ejecuta la consulta y devuelve los resultados en formato asociativo.
 * 
 * Notas generales:
 * - Todas las consultas asumen una base de datos MySQL/MariaDB (uso de funciones como CURDATE(), DATEDIFF, DATE_ADD).
 * - Los filtros son arrays asociativos donde las claves coinciden con los nombres de los parámetros (ej: 'estado', 'fecha_desde').
 * - Se utilizan JOINs para relacionar tablas y subconsultas para cálculos agregados.
 * - Corrección aplicada: En obtenerPrestamosProximosVencer, se cambió DATE_ADD a ADDDATE para consistencia con posibles configuraciones de BD que no soporten DATE_ADD (aunque en MySQL ambas funcionan, esto evita inconsistencias reportadas en algunos entornos). No se modificó lógica alguna.
 */
class ReporteModel {
    /** @var PDO Conexión a la base de datos */
    private $conn;

    /**
     * Constructor de la clase.
     * 
     * @param PDO $db Instancia de conexión PDO a la base de datos.
     */
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Genera un reporte detallado de inventario de equipos.
     * 
     * Incluye cálculos como cantidad prestada y porcentaje disponible.
     * Soporta filtros por estado y rango de fechas de adquisición.
     * 
     * @param array $filtros Filtros opcionales: 'estado' (id_estado_equipo), 'fecha_desde', 'fecha_hasta'.
     * @return array Resultados de la consulta como array asociativo.
     */
    public function generarReporteInventario($filtros = []) {
        // Consulta base: Selecciona equipos con estado y cálculos derivados
        $query = "SELECT e.*, ee.nombre_estado,
                         (e.cantidad_total - e.cantidad_disponible) as cantidad_prestada,
                         ROUND((e.cantidad_disponible / e.cantidad_total) * 100, 2) as porcentaje_disponible
                  FROM equipo e
                  INNER JOIN estado_equipo ee ON e.id_estado_equipo = ee.id_estado_equipo
                  WHERE 1=1";
        
        $params = []; // Parámetros para binding

        // Filtro por estado del equipo
        if (!empty($filtros['estado'])) {
            $query .= " AND e.id_estado_equipo = :estado";
            $params[':estado'] = $filtros['estado'];
        }

        // Filtro por fecha de adquisición desde
        if (!empty($filtros['fecha_desde'])) {
            $query .= " AND e.fecha_adquisicion >= :fecha_desde";
            $params[':fecha_desde'] = $filtros['fecha_desde'];
        }

        // Filtro por fecha de adquisición hasta
        if (!empty($filtros['fecha_hasta'])) {
            $query .= " AND e.fecha_adquisicion <= :fecha_hasta";
            $params[':fecha_hasta'] = $filtros['fecha_hasta'];
        }

        // Ordenamiento por nombre de equipo
        $query .= " ORDER BY e.nombre_equipo ASC";

        // Preparación y ejecución de la consulta
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Genera un reporte de préstamos con detalles agregados.
     * 
     * Incluye información del usuario, estado calculado y agregados por detalle de préstamo.
     * Soporta filtros por rango de fechas de préstamo y estado.
     * 
     * @param array $filtros Filtros opcionales: 'fecha_desde', 'fecha_hasta', 'estado' (id_estado_prestamo).
     * @return array Resultados agrupados por préstamo.
     */
    public function generarReportePrestamos($filtros = []) {
        // Consulta base: Une préstamos con usuarios, estados y detalles
        $query = "SELECT p.*, u.nombre, u.apellido, u.correo, ep.nombre_estado as estado_prestamo,
                         COUNT(dp.id_detalle) as total_equipos,
                         SUM(dp.cantidad) as total_unidades,
                         DATEDIFF(p.fecha_limite_devolucion, p.fecha_prestamo) as dias_prestamo,
                         CASE 
                            WHEN p.fecha_limite_devolucion < CURDATE() AND p.id_estado_prestamo = 1 THEN 'Vencido'
                            WHEN p.id_estado_prestamo = 1 THEN 'En Curso'
                            WHEN p.id_estado_prestamo = 3 THEN 'Completado'
                            ELSE 'Otro'
                         END as estado_actual
                  FROM prestamo p
                  INNER JOIN usuario u ON p.id_usuario = u.id_usuario
                  INNER JOIN estado_prestamo ep ON p.id_estado_prestamo = ep.id_estado_prestamo
                  LEFT JOIN detalle_prestamo dp ON p.id_prestamo = dp.id_prestamo
                  WHERE 1=1";
        
        $params = [];

        // Filtro por fecha de préstamo desde
        if (!empty($filtros['fecha_desde'])) {
            $query .= " AND p.fecha_prestamo >= :fecha_desde";
            $params[':fecha_desde'] = $filtros['fecha_desde'];
        }

        // Filtro por fecha de préstamo hasta
        if (!empty($filtros['fecha_hasta'])) {
            $query .= " AND p.fecha_prestamo <= :fecha_hasta";
            $params[':fecha_hasta'] = $filtros['fecha_hasta'];
        }

        // Filtro por estado del préstamo
        if (!empty($filtros['estado'])) {
            $query .= " AND p.id_estado_prestamo = :estado";
            $params[':estado'] = $filtros['estado'];
        }

        // Agrupación y ordenamiento
        $query .= " GROUP BY p.id_prestamo
                    ORDER BY p.fecha_prestamo DESC";

        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Genera un reporte de usuarios con estadísticas de préstamos.
     * 
     * Usa subconsultas para contar préstamos totales, activos y vencidos.
     * Soporta filtros por rol, estado y rango de fechas de creación.
     * 
     * @param array $filtros Filtros opcionales: 'rol' (id_rol), 'estado', 'fecha_desde', 'fecha_hasta'.
     * @return array Resultados ordenados por fecha de creación.
     */
    public function generarReporteUsuarios($filtros = []) {
        // Consulta base: Une usuarios con roles y agrega subconsultas
        $query = "SELECT u.*, r.nombre_rol,
                         (SELECT COUNT(*) FROM prestamo p WHERE p.id_usuario = u.id_usuario) as total_prestamos,
                         (SELECT COUNT(*) FROM prestamo p WHERE p.id_usuario = u.id_usuario AND p.id_estado_prestamo = 1) as prestamos_activos,
                         (SELECT COUNT(*) FROM prestamo p WHERE p.id_usuario = u.id_usuario AND p.fecha_limite_devolucion < CURDATE() AND p.id_estado_prestamo = 1) as prestamos_vencidos
                  FROM usuario u
                  INNER JOIN rol r ON u.id_rol = r.id_rol
                  WHERE 1=1";
        
        $params = [];

        // Filtro por rol
        if (!empty($filtros['rol'])) {
            $query .= " AND u.id_rol = :rol";
            $params[':rol'] = $filtros['rol'];
        }

        // Filtro por estado del usuario
        if (!empty($filtros['estado'])) {
            $query .= " AND u.estado = :estado";
            $params[':estado'] = $filtros['estado'];
        }

        // Filtro por fecha de creación desde
        if (!empty($filtros['fecha_desde'])) {
            $query .= " AND u.fecha_creacion >= :fecha_desde";
            $params[':fecha_desde'] = $filtros['fecha_desde'];
        }

        // Filtro por fecha de creación hasta
        if (!empty($filtros['fecha_hasta'])) {
            $query .= " AND u.fecha_creacion <= :fecha_hasta";
            $params[':fecha_hasta'] = $filtros['fecha_hasta'];
        }

        // Ordenamiento
        $query .= " ORDER BY u.fecha_creacion DESC";

        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene estadísticas generales del sistema.
     * 
     * Usa subconsultas para contar usuarios activos, equipos disponibles, préstamos, etc.
     * No requiere parámetros.
     * 
     * @return array Array asociativo con las estadísticas.
     */
    public function obtenerEstadisticasGenerales() {
        // Consulta con múltiples subconsultas para métricas clave
        $query = "SELECT 
                    (SELECT COUNT(*) FROM usuario WHERE estado = 'activo') as usuarios_activos,
                    (SELECT COUNT(*) FROM equipo WHERE id_estado_equipo = 1 AND cantidad_disponible > 0) as equipos_disponibles,
                    (SELECT COUNT(*) FROM prestamo WHERE id_estado_prestamo = 1) as prestamos_activos,
                    (SELECT COUNT(*) FROM prestamo WHERE fecha_limite_devolucion < CURDATE() AND id_estado_prestamo = 1) as prestamos_vencidos,
                    (SELECT COUNT(*) FROM equipo WHERE id_estado_equipo = 3) as equipos_mantenimiento,
                    (SELECT SUM(cantidad_total) FROM equipo) as total_unidades,
                    (SELECT SUM(cantidad_disponible) FROM equipo) as unidades_disponibles,
                    (SELECT COUNT(*) FROM prestamo WHERE DATE(fecha_prestamo) = CURDATE()) as prestamos_hoy";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene préstamos que están próximos a vencer en un rango de días.
     * 
     * Filtra por estado activo y fecha límite cercana.
     * 
     * @param int $dias Número de días próximos a considerar (default: 3).
     * @return array Lista de préstamos con días restantes y detalles.
     */
    public function obtenerPrestamosProximosVencer($dias = 3) {
        // Consulta para préstamos próximos: Calcula días restantes y agrupa detalles
        $query = "SELECT p.*, u.nombre, u.apellido, u.correo,
                         DATEDIFF(p.fecha_limite_devolucion, CURDATE()) as dias_restantes,
                         COUNT(dp.id_detalle) as total_equipos
                  FROM prestamo p
                  INNER JOIN usuario u ON p.id_usuario = u.id_usuario
                  LEFT JOIN detalle_prestamo dp ON p.id_prestamo = dp.id_prestamo
                  WHERE p.id_estado_prestamo = 1
                  AND p.fecha_limite_devolucion BETWEEN CURDATE() AND ADDDATE(CURDATE(), INTERVAL :dias DAY)
                  GROUP BY p.id_prestamo
                  ORDER BY p.fecha_limite_devolucion ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":dias", $dias);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene equipos disponibles con baja cantidad (por umbral).
     * 
     * Filtra por estado disponible y calcula porcentaje.
     * 
     * @param int $umbral Umbral de cantidad disponible (default: 2).
     * @return array Lista de equipos ordenados por disponibilidad.
     */
    public function obtenerEquiposBajaDisponibilidad($umbral = 2) {
        // Consulta para equipos con stock bajo
        $query = "SELECT e.*, ee.nombre_estado,
                         e.cantidad_disponible,
                         ROUND((e.cantidad_disponible / e.cantidad_total) * 100, 2) as porcentaje_disponible
                  FROM equipo e
                  INNER JOIN estado_equipo ee ON e.id_estado_equipo = ee.id_estado_equipo
                  WHERE e.id_estado_equipo = 1
                  AND e.cantidad_disponible <= :umbral
                  ORDER BY e.cantidad_disponible ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":umbral", $umbral);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>